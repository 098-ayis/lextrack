<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\DocumentRequest;
use App\Notifications\DocumentAcceptedNotification;
use App\Notifications\DocumentRejectedNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class DocumentRequests extends Page
{
    use WithPagination;

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Requests';

    protected static ?string $title = 'Document Requests';

    protected string $view = 'filament.pages.document-requests';

    public string $activeSection = 'pending';

    public string $search = '';

    public string $typeFilter = '';

    public string $dateFilter = '';

    public int $perPage = 10;

    public function mount(): void
    {
        $section = request()->query('section', 'pending');

        $this->activeSection = in_array(
            $section,
            [
                'pending',
                'accepted',
                'rejected',
            ],
            true
        ) ? $section : 'pending';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getStatusCounts(): array
    {
        $counts = DocumentRequest::query()
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'accepted' => (int) ($counts['accepted'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    public function getDocumentRequests(string $section = 'pending')
    {
        $status = match ($section) {
            'accepted' => 'accepted',
            'rejected' => 'rejected',
            default => 'pending',
        };

        return DocumentRequest::query()
            ->with([
                'document.type',
                'document.user',
                'user',
            ])
            ->where('status', $status)

            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search) {
                    $query
                        ->whereHas('document', function ($query) use ($search) {
                            $query
                                ->where('lao_number', 'like', $search)
                                ->orWhere('office_unit', 'like', $search)
                                ->orWhere('particulars', 'like', $search);
                        })
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })

            ->when($this->typeFilter, function ($query) {
                $query->whereHas('document', function ($query) {
                    $query->where('type_id', $this->typeFilter);
                });
            })
            ->when($this->dateFilter, function ($query) {
                $query->whereDate('date_of_request', $this->dateFilter);
            })
            ->latest('date_of_request')
            ->latest('request_id')
            ->paginate($this->perPage);
    }

    /**
     * ACCEPT REQUEST
     */
    public function acceptRequest(int $requestId): void
    {
        $result = DB::transaction(function () use ($requestId) {

            $request = DocumentRequest::query()
                ->with([
                    'document.user',
                    'user',
                ])
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($request->status !== 'pending') {
                return null;
            }

            $document = $request->document;

            if (!$document) {
                return null;
            }

            /*
             * Only generate an LAO number if this document
             * doesn't already have one.
             */
            if (!$document->lao_number) {

                $year = now()->format('y');

                $existingNumbers = Document::query()
                    ->whereNotNull('lao_number')
                    ->where(
                        'lao_number',
                        'like',
                        "LAO-{$year}-%"
                    )
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

                $document->lao_number = sprintf(
                    'LAO-%s-%03d',
                    $year,
                    $nextNumber
                );
            }

            $document->status = 'in_progress';
            $document->save();

            $request->update([
                'status' => 'accepted',
                'date_processed' => now()->toDateString(),
            ]);

            return [
                'request' => $request->fresh([
                    'user',
                    'document.user',
                ]),
                'document' => $document->fresh('user'),
            ];
        });

        if (!$result) {
            Notification::make()
                ->title('Unable to accept request')
                ->danger()
                ->send();

            return;
        }

        $request = $result['request'];
        $document = $result['document'];

        /*
         * Prefer the user attached to the request.
         * Fall back to the document owner.
         */
        $client = $request->user ?? $document->user;

        if ($client) {

            // EMAIL
            $client->notify(
                new DocumentAcceptedNotification($document)
            );

            // CLIENT FILAMENT BELL
            Notification::make()
                ->title('Document Accepted')
                ->body(
                    'Your document has been accepted and assigned LAO number ' .
                    $document->lao_number . '.'
                )
                ->success()
                ->sendToDatabase($client);
        }

        // ADMIN TOAST
        Notification::make()
            ->title('Document accepted')
            ->body(
                'Assigned LAO Number: ' .
                $document->lao_number
            )
            ->success()
            ->send();

        $this->redirect(
            self::getUrl([
                'section' => 'accepted',
            ])
        );
    }

    /**
     * REJECT REQUEST WITH REASON
     */
    public function rejectRequestAction(): Action
    {
        return Action::make('rejectRequest')
            ->label('')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->extraAttributes([
                'class' => 'inline-flex h-9 items-center justify-center rounded-md bg-red-600 px-3 text-xs font-semibold text-white transition hover:bg-red-700',
            ])
            ->modalHeading('Reject Document Request')
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
            ->action(function (
                array $data,
                array $arguments
            ): void {

                $request = DocumentRequest::query()
                    ->with([
                        'document.user',
                        'user',
                    ])
                    ->findOrFail(
                        $arguments['request']
                    );

                $document = $request->document;

                if (!$document) {
                    Notification::make()
                        ->title('Document not found')
                        ->danger()
                        ->send();

                    return;
                }

                DB::transaction(function () use (
                    $request,
                    $document,
                    $data
                ) {
                    $document->update([
                        'status' => 'rejected',
                        'rejection_reason' =>
                            $data['rejection_reason'],
                    ]);

                    $request->update([
                        'status' => 'rejected',
                        'date_processed' =>
                            now()->toDateString(),
                    ]);
                });

                $client =
                    $request->user ??
                    $document->user;

                if ($client) {

                    // EMAIL
                    $client->notify(
                        new DocumentRejectedNotification(
                            $document
                        )
                    );

                    // CLIENT FILAMENT BELL
                    Notification::make()
                        ->title('Document Rejected')
                        ->body(
                            'Your document has been rejected. Reason: ' .
                            $document->rejection_reason
                        )
                        ->danger()
                        ->sendToDatabase($client);
                }

                // ADMIN TOAST
                Notification::make()
                    ->title('Document rejected')
                    ->body(
                        'The client has been notified by email and in-app notification.'
                    )
                    ->success()
                    ->send();

                $this->redirect(
                    self::getUrl([
                        'section' => 'rejected',
                    ])
                );
            });
    }

    /**
     * RETURN REQUEST TO PENDING
     */
    public function returnRequest(int $requestId): void
    {
        $request = DocumentRequest::with('document')
            ->findOrFail($requestId);

        $request->update([
            'status' => 'pending',
            'date_processed' => null,
        ]);

        /*
         * Optional:
         * If a rejected request is returned to pending,
         * reset its document status too.
         */
        if (
            $request->document &&
            $request->document->status === 'rejected'
        ) {
            $request->document->update([
                'status' => 'pending',
                'rejection_reason' => null,
            ]);
        }

        $this->redirect(
            self::getUrl([
                'section' => 'pending',
            ])
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
}