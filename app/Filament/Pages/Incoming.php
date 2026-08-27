<?php

namespace App\Filament\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Livewire\WithPagination;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Models\ActionType;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Incoming extends Page
{
    use WithPagination;
    
    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected string $view = 'filament.pages.incoming';

    public string $search = '';

    public string $typeFilter = '';

    public int $perPage = 10;

    public function getMaxContentWidth(): Width
    {
        return Width::ScreenTwoExtraLarge;
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

    public function getDocuments()
    {
        return Document::query()
            ->with(['user', 'type', 'actionType'])
            ->where('status', 'in_progress')

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

    public function markAsOutgoing(int $documentId): void
    {
        $document = Document::findOrFail($documentId);

        $document->update([
            'status' => 'outgoing',
            'outgoing_date' => now(),
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
}