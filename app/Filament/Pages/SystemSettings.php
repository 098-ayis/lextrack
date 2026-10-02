<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Resources\Users\UserResource;
use App\Services\AuditLogService;
use App\Services\SystemSettingService;
use App\Support\RoleSecurity;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'System Settings';

    protected static ?string $title = 'System Settings';

    protected string $view = 'filament.pages.system-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(RoleSecurity::SUPER_ADMIN) ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill(app(SystemSettingService::class)->formData());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Management')
                    ->description('Account status and activation are managed per user, so the account history stays auditable.')
                    ->schema([
                        Placeholder::make('account_management_links')
                            ->label('Manage accounts')
                            ->content(fn (): HtmlString => new HtmlString(
                                '<div class="system-settings-link-list">'
                                .'<a href="'.e(UserResource::getUrl('index')).'">'
                                .'<span>Users</span><small>Activate, deactivate, suspend, or change account status.</small></a>'
                                .'<a href="'.e(FormerUsers::getUrl()).'">'
                                .'<span>Former Users</span><small>Review soft-deleted accounts and restore them.</small></a>'
                                .'</div>'
                            )),
                    ]),

                Section::make('Security')
                    ->description('Control authentication and session-protection defaults.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('session_timeout_minutes')
                            ->label('Session timeout')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(480)
                            ->suffix('minutes')
                            ->helperText('Idle sessions are signed out after this period.')
                            ->required(),
                        TextInput::make('allowed_email_domain')
                            ->label('Allowed BU email domain')
                            ->prefix('@')
                            ->maxLength(255)
                            ->helperText('Google login accepts only this domain.')
                            ->required(),
                        TextInput::make('login_rate_limit_per_minute')
                            ->label('Login rate limit')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->suffix('attempts/minute/IP')
                            ->helperText('Limits Google login redirect attempts from one IP address.')
                            ->required(),
                    ]),

                Section::make('Notifications')
                    ->description('Choose whether LexTrack generates system notifications and scheduled reminders.')
                    ->schema([
                        Toggle::make('system_notifications_enabled')
                            ->label('Enable system notifications')
                            ->helperText('Controls LexTrack database and email notifications.')
                            ->default(true),
                        Toggle::make('reminder_notifications_enabled')
                            ->label('Enable reminder notifications')
                            ->helperText('Controls calendar and document-deadline reminders.')
                            ->default(true),
                    ]),

                Section::make('System')
                    ->description('Set the system identity and upload policy used throughout LexTrack.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('system_name')
                            ->label('System name')
                            ->maxLength(100)
                            ->required(),
                        TextInput::make('upload_size_limit_mb')
                            ->label('Upload-size limit')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('MB per file')
                            ->helperText('Applied to document, revision, request, and message attachments.')
                            ->required(),
                        Textarea::make('office_information')
                            ->label('Office information')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        app(SystemSettingService::class)->saveMany([
            SystemSettingService::SESSION_TIMEOUT_MINUTES => (int) $data['session_timeout_minutes'],
            SystemSettingService::ALLOWED_EMAIL_DOMAIN => strtolower(ltrim(trim((string) $data['allowed_email_domain']), '@')),
            SystemSettingService::LOGIN_RATE_LIMIT_PER_MINUTE => (int) $data['login_rate_limit_per_minute'],
            SystemSettingService::SYSTEM_NOTIFICATIONS_ENABLED => (bool) ($data['system_notifications_enabled'] ?? false),
            SystemSettingService::REMINDER_NOTIFICATIONS_ENABLED => (bool) ($data['reminder_notifications_enabled'] ?? false),
            SystemSettingService::SYSTEM_NAME => trim((string) $data['system_name']),
            SystemSettingService::OFFICE_INFORMATION => trim((string) $data['office_information']),
            SystemSettingService::UPLOAD_SIZE_LIMIT_MB => (int) $data['upload_size_limit_mb'],
        ]);

        app(AuditLogService::class)->record(
            'Setting changed',
            'System settings were updated by a Super Admin.',
        );

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('The updated settings are now active.')
            ->send();
    }
}
