<?php

namespace App\Filament\Pages;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class Incoming extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected string $view = 'filament.pages.incoming';

    public string $search = '';

    /**
     * Statistics displayed on the page.
     */
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

    /**
     * Get incoming documents.
     *
     * Documents are grouped by their creation date.
     */
    public function getGroupedDocuments()
    {
        return Document::query()
            ->when(
                $this->search !== '',
                function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($q) use ($search) {
                        $q->where(
                            'particulars',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'lao_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'office_unit',
                            'like',
                            "%{$search}%"
                        );
                    });
                }
            )
            ->with([
                'type',
                'client',
            ])
            ->latest()
            ->get()
            ->groupBy(
                fn (Document $document) =>
                    $document->created_at->format('F j, Y')
            );
    }

    /**
     * Form used for adding an incoming document.
     */
    protected function documentFormSchema(): array
    {
        return [

            TextInput::make('lao_number')
                ->label('LAO/E/C/LO No.')
                ->placeholder('Enter document number')
                ->maxLength(255),

            Select::make('type_id')
                ->label('Type')
                ->options(
                    fn () =>
                        DocumentType::query()
                            ->orderBy('type_name')
                            ->pluck('type_name', 'type_id')
                            ->toArray()
                            + [
                                'others' => 'Others',
                            ]
                )
                ->searchable()
                ->preload()
                ->live()
                ->required(),

            TextInput::make('type_other')
                ->label('Specify Type')
                ->placeholder('Enter document type')
                ->visible(
                    fn (Get $get): bool =>
                        $get('type_id') === 'others'
                )
                ->required(
                    fn (Get $get): bool =>
                        $get('type_id') === 'others'
                )
                ->maxLength(255),

            Select::make('client_id')
                ->label('Client')
                ->options(
                    fn () =>
                        Client::query()
                            ->orderBy('office')
                            ->pluck('office', 'client_id')
                            ->toArray()
                )
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('office_unit')
                ->label('Office/Unit')
                ->placeholder('Enter office or unit')
                ->maxLength(255),

            Textarea::make('particulars')
                ->label('Particulars')
                ->placeholder('Enter document particulars...')
                ->rows(4)
                ->required(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'returned' => 'Returned',
                    'archived' => 'Archived',
                    'others' => 'Others',
                ])
                ->default('pending')
                ->live()
                ->required(),

            TextInput::make('status_other')
                ->label('Specify Status')
                ->placeholder('Enter document status')
                ->visible(
                    fn (Get $get): bool =>
                        $get('status') === 'others'
                )
                ->required(
                    fn (Get $get): bool =>
                        $get('status') === 'others'
                )
                ->maxLength(255),

            DatePicker::make('deadline')
                ->label('Deadline')
                ->native(false),

        ];
    }

    /**
     * Resolve "Others" selections before saving.
     */
    protected function resolveOthers(array $data): array
    {
        /*
         * Handle document type.
         */
        if (($data['type_id'] ?? null) === 'others') {

            $typeName = trim($data['type_other'] ?? '');

            if ($typeName !== '') {
                $type = DocumentType::firstOrCreate([
                    'name' => $typeName,
                ]);

                $data['type_id'] = $type->type_id;
            }
        }

        unset($data['type_other']);

        /*
         * Handle custom status.
         */
        if (($data['status'] ?? null) !== 'others') {
            $data['status_other'] = null;
        }

        return $data;
    }

    /**
     * Add Document action.
     */
    public function addDocumentAction(): Action
    {
        return Action::make('addDocument')
            ->label('Add Documents')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading('Add New Document')
            ->modalDescription(
                'Enter the details of the incoming document.'
            )
            ->modalSubmitActionLabel('Save Document')
            ->modalCancelActionLabel('Cancel')
            ->form($this->documentFormSchema())
            ->action(function (array $data): void {

                /*
                 * Associate the document with
                 * the currently authenticated user.
                 */
                $data['user_id'] = auth()->id();

                /*
                 * Resolve "Others" values.
                 */
                $data = $this->resolveOthers($data);

                /*
                 * Create the document.
                 *
                 * The Document model's created event
                 * will automatically create its Conversation.
                 */
                Document::create($data);
            });
    }
}