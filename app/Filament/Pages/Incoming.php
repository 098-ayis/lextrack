<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\RejectedDocument;
use App\Notifications\DocumentRejectedNotification;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\WithPagination;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Models\ActionType;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class Incoming extends Page
{
    use WithPagination;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $title = 'Documents';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected string $view = 'filament.pages.incoming';

    public string $search = '';

    public string $typeFilter = '';

    public string $dateFilter = '';

    public int $perPage = 10;

    public string $activeSection = 'incoming';

    public bool $showAcceptedModal = false;

    public ?string $acceptedDocumentUploader = null;

    public function mount(): void
    {
        $section = request()->query('section', 'incoming');

        $this->activeSection = in_array($section, [
            'pending',
            'incoming',
            'outgoing',
            'archive',
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
            'total' => Document::count(),

            'pending' => Document::where('status', 'pending')
                ->count(),

            'active' => Document::where('status', 'in_progress')
                ->count(),

            'completed' => Document::where('status', 'completed')
                ->count(),
        ];
    }

    public function getStatusCounts(): array
    {
        $counts = Document::query()
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereIn('status', [
                'pending',
                'in_progress',
                'outgoing',
                'archived',
                'rejected',
            ])
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'incoming' => (int) ($counts['in_progress'] ?? 0),
            'outgoing' => (int) ($counts['outgoing'] ?? 0),
            'archive' => (int) ($counts['archived'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    public function getDocuments(string $section = 'incoming')
    {
        $status = match ($section) {
            'pending' => 'pending',
            'incoming' => 'in_progress',
            'outgoing' => 'outgoing',
            'rejected' => 'rejected',
            'archive' => 'archived',
            default => 'in_progress',
        };

        return Document::query()
            ->with(['user', 'type', 'actionType', 'rejections'])
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
                    ->disk('public')
                    ->directory('documents')
                    ->preserveFilenames(),
            ])
            ->action(function (array $data) {
                $data['user_id'] = auth()->id();

                Document::create($data);
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
                        'archived' => 'Archived',
                        'outgoing' => 'Outgoing',
                    ])
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->disk('public')
                    ->directory('documents')
                    ->preserveFilenames(),
            ])
            ->fillForm(function (array $arguments): array {
                $document = Document::findOrFail($arguments['document']);

                return [
                    'lao_number' => $document->lao_number,
                    'type_id' => $document->type_id,

                    // Important: preload current Action Taken
                    'action_id' => $document->action_id,

                    'office_unit' => $document->office_unit,
                    'particulars' => $document->particulars,
                    'deadline' => $document->deadline,
                    'status' => $document->status,
                    'file_path' => $document->file_path,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $document = Document::findOrFail($arguments['document']);

                $document->update($data);
            });
    }

    public function acceptDocument(int $documentId): void
    {
        $document = Document::with('user')->findOrFail($documentId);

        $document->update([
            'status' => 'in_progress',
        ]);

        $this->acceptedDocumentUploader = $document->user?->name;
        $this->showAcceptedModal = true;
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
            ->modalHeading('Accept Document')
            ->modalDescription(function (array $arguments): string {
                $document = Document::with('user')->find($arguments['document'] ?? null);
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
        $document = Document::findOrFail($documentId);

        $document->update([
            'status' => 'outgoing',
            'sent_date' => $sentDate,
            'sent_to' => $sentTo,
        ]);

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
            ->modalHeading('Reject Document')
            ->modalDescription('Please provide a reason for rejecting this document.')
            ->schema([
                Textarea::make('reason')
                    ->label('Rejection Reason')
                    ->required()
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->action(function (array $data, array $arguments): void {
                $this->rejectDocument(
                    (int) $arguments['document'],
                    $data['reason'],
                );
            });
    }

    public function rejectDocument(int $documentId, string $reason): void
    {
        $document = Document::with('user')->findOrFail($documentId);

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
                $document = Document::findOrFail($arguments['document']);
                $isOutgoing = $data['destination'] === 'outgoing';

                $document->update([
                    'status' => $isOutgoing ? 'outgoing' : 'in_progress',
                ]);

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

    public function archiveDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);

        $document->update([
            'status' => 'archived',
        ]);

        Notification::make()
            ->success()
            ->title('Document archived')
            ->body('The document was successfully marked as completed and moved to the Archive table.')
            ->send();

        $this->redirect(self::getUrl(['section' => 'archive']));
    }

    public function archiveDocumentAction(): Action
    {
        return Action::make('archiveDocument')
            ->label('')
            ->icon('heroicon-o-archive-box')
            ->color('gray')
            ->tooltip('Archive')
            ->modalHeading('Archive Document')
            ->modalDescription('Are you sure you want to archive this document? It will be marked as completed and moved to the Archive table.')
            ->modalIcon('heroicon-o-archive-box')
            ->modalIconColor('gray')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Archive document')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'inline-flex items-center justify-center w-9 h-9 rounded-md bg-[#334155] hover:bg-[#0F172A] text-white transition',
            ])
            ->action(function (array $arguments): void {
                $this->archiveDocument((int) $arguments['document']);
            });
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
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
