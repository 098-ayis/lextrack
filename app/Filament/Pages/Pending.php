<?php

namespace App\Filament\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\WithPagination;
use App\Notifications\DocumentAcceptedNotification;
use App\Notifications\DocumentRejectedNotification;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;


class Pending extends Page
{
    protected static ?int $navigationSort = 2;

    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.pending';


    protected $fillable = [
    'user_id',
    'type_id',
    'action_id',
    'lao_number',
    'office_unit',
    'particulars',
    'deadline' => 'date',
    'sent_to',
    'sent_date',
    'returned_from',
    'date_returned',
    'outgoing_date',
    'status',
    'file_path',
    ];

    public string $search = '';

    public string $typeFilter = '';

    public int $perPage = 10;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getDocuments()
    {
        return Document::query()
            ->with(['user', 'type', 'actionType'])
            ->where('status', 'pending')

            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('lao_number', 'like', $search)
                        ->orWhere('office_unit', 'like', $search)
                        ->orWhere('particulars', 'like', $search)
                        ->orWhere('sent_to', 'like', $search)
                        ->orWhere('returned_from', 'like', $search);
                });
            })

            ->when($this->typeFilter, function ($query) {
                $query->where('type_id', $this->typeFilter);
            })

            ->latest('outgoing_date')
            ->paginate($this->perPage);
    }

    public function downloadDocument(int $documentId): StreamedResponse
    {
        $document = Document::findOrFail($documentId);

        abort_unless(
            $document->file_path &&
            Storage::disk('local')->exists($document->file_path),
            404
        );

        $fileName = basename($document->file_path);

        return Storage::disk('local')->download(
            $document->file_path,
            $fileName
        );
    }

    public function acceptDocument(int $documentId): void
    {
        DB::transaction(function () use ($documentId) {
            $document = Document::with('user')
                ->lockForUpdate()
                ->findOrFail($documentId);

            // Don't assign another LAO number if already accepted.
            if ($document->status !== 'pending') {
                return;
            }

            $year = now()->format('y');

            $existingNumbers = Document::query()
                ->whereNotNull('lao_number')
                ->where('lao_number', 'like', "LAO-{$year}-%")
                ->pluck('lao_number');

            $highestNumber = $existingNumbers
                ->map(function ($laoNumber) {
                    $parts = explode('-', $laoNumber);

                    return isset($parts[2])
                        ? (int) $parts[2]
                        : 0;
                })
                ->max() ?? 0;

            $nextNumber = $highestNumber + 1;

            $laoNumber = sprintf(
                'LAO-%s-%03d',
                $year,
                $nextNumber
            );

            $document->update([
                'lao_number' => $laoNumber,
                'status' => 'in_progress',
            ]);

            if ($document->user) {
                $document->user->notify(
                    new DocumentAcceptedNotification($document)
                );

                Notification::make()
                    ->title('Document Accepted')
                    ->body(
                        'Your document has been accepted and assigned LAO number ' .
                        $document->lao_number . '.'
                    )
                    ->success()
                    ->sendToDatabase($document->user);
            }

            Notification::make()
                ->title('Document accepted')
                ->body("Assigned LAO Number: {$document->lao_number}")
                ->success()
                ->send();
        });
    }

    public function rejectDocumentAction(): Action
    {
        return Action::make('rejectDocument')
            ->label('')
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->tooltip('Reject')
            ->modalHeading('Reject Document')
            ->modalDescription(
                'Please provide the reason why this document is being rejected.'
            )
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('Reason for Rejection')
                    ->placeholder(
                        'e.g., Incomplete supporting documents, incorrect document type...'
                    )
                    ->rows(5)
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (array $data, array $arguments): void {
                $document = Document::with('user')
                    ->findOrFail($arguments['document']);

                $document->update([
                    'status' => 'rejected',
                    'rejection_reason' => $data['rejection_reason'],
                ]);

                if ($document->user) {
                    // Email
                    $document->user->notify(
                        new DocumentRejectedNotification($document)
                    );

                    // Client notification bell
                    Notification::make()
                        ->title('Document Rejected')
                        ->body(
                            'Your document has been rejected. Reason: ' .
                            $document->rejection_reason
                        )
                        ->danger()
                        ->sendToDatabase($document->user);
                }

                // Admin toast
                Notification::make()
                    ->title('Document rejected')
                    ->body('The client has been notified by email and in-app notification.')
                    ->success()
                    ->send();
            });
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
}