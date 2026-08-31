<?php

namespace App\Filament\Pages;

use App\Models\Document as DocumentModel;
use App\Models\DocumentVersion;
use App\Models\RejectedDocument;
use App\Notifications\DocumentRejectedNotification;
use App\Notifications\DocumentAcceptedNotification;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\WithPagination;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\ActionType;
use App\Models\ActivityLog;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Document extends Page
{
    use WithPagination;

    protected static ?string $slug = 'incoming';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $title = 'Documents';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected string $view = 'filament.pages.document';

    public string $search = '';

    public string $typeFilter = '';

    public string $dateFilter = '';

    public int $perPage = 10;

    public string $activeSection = 'incoming';

    public bool $showAcceptedModal = false;

    public ?string $acceptedDocumentUploader = null;

    public ?int $qrCodeDocumentId = null;

    public ?string $qrCodeSvg = null;

    public ?string $qrCodeUrl = null;

    public function mount(): void
    {
        $section = request()->query('section', 'incoming');

        $this->activeSection = in_array($section, [
            'pending',
            'incoming',
            'outgoing',
            'completed',
            'rejected',
        ], true) ? $section : 'incoming';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getStats(): array
    {
        return [
            'total' => DocumentModel::count(),

            'pending' => DocumentModel::where('status', 'pending')
                ->count(),

            'active' => DocumentModel::where('status', 'in_progress')
                ->count(),

            'completed' => DocumentModel::where('status', 'completed')
                ->count(),
        ];
    }

    public function getStatusCounts(): array
    {
        $counts = DocumentModel::query()
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereIn('status', [
                'pending',
                'in_progress',
                'outgoing',
                'completed',
                'rejected',
            ])
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'incoming' => (int) ($counts['in_progress'] ?? 0),
            'outgoing' => (int) ($counts['outgoing'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    public function acceptDocument(int $documentId): void
    {
        $result = DB::transaction(function () use ($documentId): array {

            $document = DocumentModel::with('user')
                ->lockForUpdate()
                ->findOrFail($documentId);

            if ($document->status !== 'pending') {
                return [
                    'document' => $document,
                    'accepted' => false,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 1. Generate LAO number
            |--------------------------------------------------------------------------
            */

            $year = now()->format('y');

            $existingNumbers = DocumentModel::query()
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

            /*
            |--------------------------------------------------------------------------
            | 2. Accept document
            |--------------------------------------------------------------------------
            */

            $document->update([
                'lao_number' => $laoNumber,
                'status' => 'in_progress',
            ]);

            $this->recordDocumentActivity(
                $document->document_id,
                'Document accepted',
                'Accepted the document and moved it to Incoming.'
            );
            /*
            |--------------------------------------------------------------------------
            | 3. Create conversation
            |--------------------------------------------------------------------------
            */

            $conversation = Conversation::firstOrCreate(
                [
                    'document_id' => $document->document_id,
                ],
                [
                    'created_by' => $document->user_id,
                    'assigned_to' => null,
                    'status' => 'active',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 4. Add client as participant
            |--------------------------------------------------------------------------
            */

            if ($document->user_id) {
                $conversation->participants()->syncWithoutDetaching([
                    $document->user_id => [
                        'joined_at' => now(),
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Add staff participants
            |--------------------------------------------------------------------------
            */

            $staffIds = User::role(User::ADMIN_ROLES)
                ->pluck('id');

            foreach ($staffIds as $staffId) {
                $conversation->participants()->syncWithoutDetaching([
                    $staffId => [
                        'joined_at' => now(),
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Create initial message
            |--------------------------------------------------------------------------
            */

            if (! $conversation->messages()->exists()) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => auth()->id(),
                    'body' => 'Your document has been accepted by the Legal Affairs Office and is now being processed.',
                ]);
            }

            $conversation->touch();

            return [
                'document' => $document,
                'accepted' => true,
            ];
        });

        $document = $result['document'];

        /*
        |--------------------------------------------------------------------------
        | 7. Send acceptance notification
        |--------------------------------------------------------------------------
        */

        if ($result['accepted'] && $document->user) {
            $document->user->notify(
                new DocumentAcceptedNotification($document)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Redirect
        |--------------------------------------------------------------------------
        */

        $this->redirect(
            self::getUrl(['section' => 'incoming'])
        );
    }

    public function getDocuments(string $section = 'incoming')
    {
        $status = match ($section) {
            'pending' => 'pending',
            'incoming' => 'in_progress',
            'outgoing' => 'outgoing',
            'rejected' => 'rejected',
            'completed' => 'completed',
            default => 'in_progress',
        };

        return DocumentModel::query()
            ->with(['user', 'type', 'actionType', 'rejections', 'latestVersion'])
            ->where('status', $status)

            // Search
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('lao_number', 'like', $search)
                        ->orWhere('office_unit', 'like', $search)
                        ->orWhere('particulars', 'like', $search);
                });
            })

            // Status filter
            ->when($this->typeFilter, function ($query) {
                $query->where('type_id', $this->typeFilter);
            })

            // Upload date filter
            ->when($this->dateFilter, function ($query) {
                $query->whereDate('created_at', $this->dateFilter);
            })

            ->latest('created_at')

            ->paginate($this->perPage);
    }

    public function openQrCode(int $documentId): void
    {
        try {
            DocumentModel::findOrFail($documentId);

            $url = URL::signedRoute('documents.public-status', [
                'document' => $documentId,
            ]);

            $this->qrCodeDocumentId = $documentId;
            $this->qrCodeUrl = $url;
            $this->qrCodeSvg = (new QRCode(new QROptions([
                'outputType' => QROutputInterface::MARKUP_SVG,
                'outputBase64' => false,
                'scale' => 5,
            ])))->render($url);
        } catch (\Throwable $exception) {
            report($exception);

            $this->closeQrCode();

            Notification::make()
                ->danger()
                ->title('QR code could not be generated')
                ->body('Please try again.')
                ->send();
        }
    }

    public function closeQrCode(): void
    {
        $this->qrCodeDocumentId = null;
        $this->qrCodeSvg = null;
        $this->qrCodeUrl = null;
    }

    public function addDocumentAction(): Action
    {
        return Action::make('addDocument')
            ->label('Add Document')
            ->icon('heroicon-o-plus')
            ->modalHeading('Add New Document')
            ->extraAttributes([
                'class' => 'add-document-button',
            ])

            ->schema([
                TextInput::make('lao_number')
                    ->label('LAO Number')
                    ->required(),

                Select::make('type_id')
                ->label('Document Type')
                ->placeholder('Select document type')
                ->options(
                    \App\Models\DocumentType::query()
                        ->pluck('type_name', 'type_id')
                )
                ->searchable()
                ->required(),

                Select::make('action_id')
                ->label('Action Taken')
                ->placeholder('Select action')
                ->options(
                    \App\Models\ActionType::query()
                        ->orderBy('action_id')
                        ->pluck('action_name', 'action_id')
                )
                ->searchable()
                ->nullable(),
                    
                TextInput::make('office_unit')
                    ->label('Office / Unit')
                    ->required(),

                Textarea::make('particulars')
                    ->label('Particulars')
                    ->required(),

                DatePicker::make('deadline')
                    ->label('Deadline'),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                    ])
                    ->default('in_progress')
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->disk('local')
                    ->directory('documents')
                    ->preserveFilenames(),
            ])
            ->action(function (array $data) {
                $filePath = $data['file_path'] ?? null;
                unset($data['file_path']);

                $data['user_id'] = auth()->id();

                $document = DB::transaction(function () use ($data, $filePath): DocumentModel {
                    $document = DocumentModel::create($data);

                    if (filled($filePath)) {
                        DocumentVersion::create([
                            'document_id' => $document->document_id,
                            'user_id' => auth()->id(),
                            'version_number' => '1',
                            'file_path' => $filePath,
                        ]);
                    }

                    return $document;
                });

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document created',
                    'Created a new document.'
                );
            });
    }



    public function editDocumentAction(): Action
    {
        return Action::make('editDocument')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->tooltip('Edit')
            ->extraAttributes([
                'class' => 'edit-document-button',
            ])

            ->schema(function (array $arguments): array {
                $document = DocumentModel::find($arguments['document'] ?? null);

                if ($document?->status === 'outgoing') {
                    return [
                        Select::make('type_id')
                            ->label('Document Type')
                            ->options(
                                \App\Models\DocumentType::query()
                                    ->pluck('type_name', 'type_id')
                            )
                            ->searchable()
                            ->required(),

                        DatePicker::make('outgoing_date')
                            ->label('Outgoing Date'),

                        TextInput::make('sent_to')
                            ->label('Sent To')
                            ->maxLength(255),

                        DatePicker::make('sent_date')
                            ->label('Sent Date'),

                        TextInput::make('returned_from')
                            ->label('Returned From')
                            ->maxLength(255),

                        DatePicker::make('date_returned')
                            ->label('Returned Date'),
                    ];
                }

                return [
                TextInput::make('lao_number')
                    ->label('LAO Number')
                    ->required(),

                Select::make('type_id')
                    ->label('Document Type')
                    ->placeholder('Select document type')
                    ->options(
                        \App\Models\DocumentType::query()
                            ->pluck('type_name', 'type_id')
                    )
                    ->searchable()
                    ->required(),

                Select::make('action_id')
                ->label('Action Taken')
                ->placeholder('Select action')
                ->options(
                    ActionType::query()
                        ->orderBy('action_name')
                        ->pluck('action_name', 'action_id')
                )
                ->searchable()
                ->preload()
                ->nullable(),

                TextInput::make('office_unit')
                    ->label('Office / Unit')
                    ->required(),

                Textarea::make('particulars')
                    ->label('Particulars')
                    ->required(),

                DatePicker::make('deadline')
                    ->label('Deadline'),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'returned' => 'Returned',
                        'outgoing' => 'Outgoing',
                    ])
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Upload New Document Version')
                    ->disk('local')
                    ->directory('documents/versions')
                    ->preserveFilenames(),
                ];
            })
            ->fillForm(function (array $arguments): array {
                $document = DocumentModel::findOrFail($arguments['document']);

                return [
                    'lao_number' => $document->lao_number,
                    'type_id' => $document->type_id,

                    // Important: preload current Action Taken
                    'action_id' => $document->action_id,

                    'office_unit' => $document->office_unit,
                    'particulars' => $document->particulars,
                    'deadline' => $document->deadline,
                    'status' => $document->status,
                    'outgoing_date' => $document->outgoing_date,
                    'sent_to' => $document->sent_to,
                    'sent_date' => $document->sent_date,
                    'returned_from' => $document->returned_from,
                    'date_returned' => $document->date_returned,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $document = DocumentModel::findOrFail($arguments['document']);
                $filePath = $data['file_path'] ?? null;
                unset($data['file_path']);
                $oldValues = $document->only(array_keys($data));

                $document->fill($data);

                $fieldLabels = [
                    'lao_number' => 'LAO Number',
                    'type_id' => 'Document Type',
                    'action_id' => 'Action Taken',
                    'office_unit' => 'Office / Unit',
                    'particulars' => 'Particulars',
                    'deadline' => 'Deadline',
                    'status' => 'Status',
                    'outgoing_date' => 'Outgoing Date',
                    'sent_to' => 'Sent To',
                    'sent_date' => 'Sent Date',
                    'returned_from' => 'Returned From',
                    'date_returned' => 'Returned Date',
                ];

                $updatedFields = collect(array_keys($document->getDirty()))
                    ->map(fn (string $field): string => $fieldLabels[$field] ?? $field)
                    ->values()
                    ->all();

                $document->save();

                if (filled($filePath)) {
                    DocumentVersion::create([
                        'document_id' => $document->document_id,
                        'user_id' => auth()->id(),
                        'version_number' => (string) $this->getNextVersionNumber($document),
                        'file_path' => $filePath,
                    ]);

                    $updatedFields[] = 'Document File';
                }

                $updatedSummary = $updatedFields !== []
                    ? implode(', ', $updatedFields) . ' changed'
                    : 'No fields changed';

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document updated',
                    $updatedSummary,
                    (string) json_encode($oldValues),
                    (string) json_encode($document->only(array_keys($data)))
                );

                Notification::make()
                    ->success()
                    ->title('Document updated successfully')
                    ->body(
                        $updatedSummary .
                        '. Updated on: ' . now()->format('F d, Y \a\t h:i A') . '.'
                    )
                    ->send();
            });
    }

    public function downloadDocument(int $documentId): StreamedResponse
    {
        $document = DocumentModel::with(['user', 'latestVersion'])->findOrFail($documentId);
        $version = $document->latestVersion;

        abort_unless(
            $version?->file_path &&
            $version->storageDisk()->exists($version->file_path),
            404
        );

        $disk = $version->storageDisk();

        $this->recordDocumentActivity(
            $document->document_id,
            'Document downloaded',
            'Downloaded ' . basename((string) $version->file_path) . '.'
        );

        $fileName = basename($version->file_path);

        return $disk->download(
            $version->file_path,
            $fileName
        );
    }

    protected function getNextVersionNumber(DocumentModel $document): int
    {
        $highestVersion = $document->versions()
            ->pluck('version_number')
            ->map(function ($versionNumber): int {
                preg_match('/(\d+)\s*$/', (string) $versionNumber, $matches);

                return (int) ($matches[1] ?? 0);
            })
            ->max() ?? 0;

        return max(1, $highestVersion + 1);
    }

    public function redirectToIncoming(): void
    {
        $this->redirect(self::getUrl(['section' => 'incoming']));
    }

    public function acceptDocumentAction(): Action
    {
        return Action::make('acceptDocument')
            ->label('Accept')
            ->icon('heroicon-o-check')
            ->color('success')
            ->size('xs')
            ->modalHeading('Accept Document')
            ->modalDescription(function (array $arguments): string {
                $document = DocumentModel::with('user')->find($arguments['document'] ?? null);
                $uploader = $document?->user?->name ?? 'Unknown user';

                return "Are you sure you want to accept this document uploaded by {$uploader}? It will be moved to the Incoming table.";
            })
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Accept document')
            ->modalCancelActionLabel('Cancel')
            ->action(function (array $arguments): void {
                $this->acceptDocument((int) $arguments['document']);
            });
    }

    public function markAsOutgoing(int $documentId, string $sentDate, string $sentTo): void
    {
        $document = DocumentModel::findOrFail($documentId);

        $document->update([
            'status' => 'outgoing',
            'sent_date' => $sentDate,
            'sent_to' => $sentTo,
        ]);

        $this->recordDocumentActivity(
            $document->document_id,
            'Document moved to outgoing',
            'Sent to ' . $sentTo . ' on ' . $sentDate . '.'
        );

        Notification::make()
            ->success()
            ->title('Document added to Outgoing')
            ->body('The document was successfully added to the Outgoing table.')
            ->send();

        $this->redirect(self::getUrl(['section' => 'outgoing']));
    }

    public function markAsOutgoingAction(): Action
    {
        return Action::make('markAsOutgoing')
            ->label('Outgoing')
            ->icon('heroicon-o-arrow-right')
            ->color('gray')
            ->modalHeading('Add Document to Outgoing')
            ->modalDescription('Provide the destination and sent date for this document.')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Add to outgoing')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'outgoing-document-button',
            ])
            ->schema([
                TextInput::make('sent_to')
                    ->label('Sent To')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('sent_date')
                    ->label('Sent Date')
                    ->default(now())
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $this->markAsOutgoing(
                    (int) $arguments['document'],
                    $data['sent_date'],
                    $data['sent_to'],
                );
            });
    }

    public function rejectDocumentAction(): Action
    {
        return Action::make('rejectDocument')
            ->label('Reject')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->size('xs')
            ->modalHeading('Reject Document')
            ->modalDescription('Please provide a reason for rejecting this document.')
            ->schema([
                Select::make('reason')
                    ->label('Rejection Reason')
                    ->placeholder('Select a reason')
                    ->options([
                        'Incomplete or missing information' => 'Incomplete or missing information',
                        'Incorrect document type' => 'Incorrect document type',
                        'Missing signature or approval' => 'Missing signature or approval',
                        'Duplicate document' => 'Duplicate document',
                        'Incorrect recipient or office' => 'Incorrect recipient or office',
                        'Unreadable or corrupted file' => 'Unreadable or corrupted file',
                        'other' => 'Other',
                    ])
                    ->live()
                    ->required(),

                Textarea::make('custom_reason')
                    ->label('Specify Other Reason')
                    ->placeholder('Type the reason for rejecting this document')
                    ->visible(fn (Get $get): bool => $get('reason') === 'other')
                    ->required(fn (Get $get): bool => $get('reason') === 'other')
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->action(function (array $data, array $arguments): void {
                $reason = $data['reason'] === 'other'
                    ? trim((string) ($data['custom_reason'] ?? ''))
                    : $data['reason'];

                $this->rejectDocument(
                    (int) $arguments['document'],
                    $reason,
                );
            });
    }

    public function rejectDocument(int $documentId, string $reason): void
    {
        $document = DocumentModel::with('user')->findOrFail($documentId);

        $rejection = DB::transaction(function () use ($document, $reason): RejectedDocument {
            $rejection = RejectedDocument::create([
                'document_id' => $document->document_id,
                'reason' => $reason,
            ]);

            $document->update([
                'status' => 'rejected',
            ]);

            return $rejection;
        });

        if ($document->user) {
            $document->user->notify(
                new DocumentRejectedNotification($document, $rejection)
            );
        }

        $this->recordDocumentActivity(
            $document->document_id,
            'Document rejected',
            'Rejected the document: ' . $reason
        );

        $this->redirect(self::getUrl(['section' => 'rejected']));
    }

    public function returnDocumentAction(): Action
    {
        return Action::make('returnDocument')
            ->label('Return')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->tooltip('Return Document')
            ->extraAttributes([
                'class' => 'return-document-button',
            ])
            ->modalHeading('Return Document')
            ->modalDescription('Choose which document section this document should be returned to.')
            ->schema([
                Select::make('destination')
                    ->label('Return to')
                    ->options([
                        'incoming' => 'Incoming',
                        'outgoing' => 'Outgoing',
                    ])
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                        $document = DocumentModel::findOrFail($arguments['document']);
                $isOutgoing = $data['destination'] === 'outgoing';

                $document->update([
                    'status' => $isOutgoing ? 'outgoing' : 'in_progress',
                ]);

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document returned',
                    'Returned the document to ' . $data['destination'] . '.'
                );

                $this->redirect(self::getUrl([
                    'section' => $isOutgoing ? 'outgoing' : 'incoming',
                ]));
            });
    }

    public function viewRejectionReasonAction(): Action
    {
        return Action::make('viewRejectionReason')
            ->label('View reason')
            ->link()
            ->color('danger')
            ->modalHeading('Rejection Reason')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->fillForm(function (array $arguments): array {
                $rejection = RejectedDocument::findOrFail(
                    (int) $arguments['rejection']
                );

                $this->recordDocumentActivity(
                    $rejection->document_id,
                    'Rejection reason viewed',
                    'Viewed the document rejection reason.'
                );

                return [
                    'reason' => $rejection->reason,
                ];
            })
            ->schema([
                Textarea::make('reason')
                    ->label('Reason')
                    ->disabled()
                    ->rows(5)
                    ->dehydrated(false),
            ]);
    }

    public function completeDocument(int $documentId): void
    {
        $document = DocumentModel::findOrFail($documentId);

        $document->update([
            'status' => 'completed',
        ]);

        $this->recordDocumentActivity(
            $document->document_id,
            'Document completed',
            'Marked the document as completed.'
        );

        Notification::make()
            ->success()
            ->title('Document completed')
            ->body('The document was successfully marked as completed and moved to the Completed table.')
            ->send();

        $this->redirect(self::getUrl(['section' => 'completed']));
    }

    public function completeDocumentAction(): Action
    {
        return Action::make('completeDocument')
            ->label('Complete')
            ->color('gray')
            ->tooltip('Complete')
            ->modalHeading('Complete Document')
            ->modalDescription('Are you sure you want to mark this document as completed? It will be moved to the Completed table.')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('gray')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Complete document')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold bg-[#334155] hover:bg-[#0F172A] text-white transition',
            ])
            ->action(function (array $arguments): void {
                $this->completeDocument((int) $arguments['document']);
            });
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }


    public function messageDocument(int $documentId): void
    {
        $document = DocumentModel::findOrFail($documentId);

        $this->recordDocumentActivity(
            $document->document_id,
            'Message opened',
            'Opened the document conversation.'
        );

        $this->redirect(
            route('filament.admin.pages.messages', [
                'document' => $document->document_id,
            ])
        );
    }

    protected function recordDocumentActivity(
        int $documentId,
        string $actionType,
        string $actionDetails = '',
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'document_id' => $documentId,
            'action_type' => $actionType,
            'action_details' => $actionDetails !== ''
                ? $actionDetails
                : $actionType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
    
    
    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'sent_date' => 'date',
            'date_returned' => 'date',
            'outgoing_date' => 'date',
        ];
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
}
