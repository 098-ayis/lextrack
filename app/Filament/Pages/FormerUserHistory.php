<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use App\Models\Calendar as CalendarModel;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentVersion;
use App\Models\Message;
use App\Models\Note;
use App\Models\User;
use App\Support\RoleSecurity;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class FormerUserHistory extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'former-users/{user}/history';

    protected static ?string $title = 'Former User History';

    protected string $view = 'filament.pages.former-user-history';

    public User $user;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(RoleSecurity::SUPER_ADMIN) ?? false;
    }

    public function mount(string|int $user): void
    {
        $this->user = User::withTrashed()->with('roles')->findOrFail($user);

        abort_unless($this->user->trashed(), 404);
    }

    public function getHeading(): string|Htmlable
    {
        return $this->user->historical_name;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restore')
                ->label('Restore User')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore user?')
                ->modalDescription('This user will return to normal user management and may authenticate again if their account status allows it.')
                ->authorize(fn (): bool => UserResource::canRestore($this->user))
                ->action(function (): void {
                    abort_unless(UserResource::canRestore($this->user), 403);

                    $this->user->restore();

                    Notification::make()
                        ->success()
                        ->title('User restored')
                        ->send();

                    $this->redirect(FormerUsers::getUrl(), navigate: true);
                }),
        ];
    }

    protected function getViewData(): array
    {
        $userId = $this->user->getKey();

        return [
            'documents' => Document::query()
                ->where('user_id', $userId)
                ->latest('created_at')
                ->get(),
            'requests' => DocumentRequest::query()
                ->with('document')
                ->where('user_id', $userId)
                ->latest('date_of_request')
                ->latest('request_id')
                ->get(),
            'versions' => DocumentVersion::query()
                ->with('document')
                ->where('user_id', $userId)
                ->latest('created_at')
                ->latest('version_id')
                ->get(),
            'messages' => Message::query()
                ->with('conversation.document', 'conversation.documentRequest')
                ->where('sender_id', $userId)
                ->latest('created_at')
                ->latest('id')
                ->get(),
            'notes' => Note::query()
                ->with('document')
                ->where('user_id', $userId)
                ->latest('created_at')
                ->latest('note_id')
                ->get(),
            'auditLogs' => ActivityLog::query()
                ->with('document')
                ->where('user_id', $userId)
                ->latest('created_at')
                ->latest('log_id')
                ->get(),
            'conversations' => Conversation::query()
                ->with('document', 'documentRequest', 'creator', 'participants')
                ->where(function (Builder $query) use ($userId): void {
                    $query
                        ->where('created_by', $userId)
                        ->orWhereHas('participants', function (Builder $query) use ($userId): void {
                            $query->where('users.id', $userId);
                        });
                })
                ->latest('created_at')
                ->latest('id')
                ->get(),
            'calendarEvents' => CalendarModel::query()
                ->with('documentRequest')
                ->where('user_id', $userId)
                ->latest('date')
                ->latest('sched_id')
                ->get(),
        ];
    }
}
