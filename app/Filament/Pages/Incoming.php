<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentType;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Page;

class Incoming extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected string $view = 'filament.pages.incoming';

    public string $search = '';

    /**
     * Card totals shown at the top of the page.
     */
    public function getStats(): array
    {
        return [
            'total' => Document::count(),
            'pending' => Document::where('status', 'pending')->count(),
            'active' => Document::where('status', 'in_progress')->count(),
            'completed' => Document::where('status', 'completed')->count(),
        ];
    }

    /**
     * Documents grouped by the day they were created, newest group first.
     */
    public function getGroupedDocuments()
    {
        return Document::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('particulars', 'like', "%{$this->search}%")
                        ->orWhere('lao_number', 'like', "%{$this->search}%")
                        ->orWhere('office_unit', 'like', "%{$this->search}%");
                });
            })
            ->with(['type', 'client'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (Document $document) => $document->created_at->format('F j, Y'));
    }

    /**
     * Shared field schema for the "Add new document" form,
     * including the "Others" option for Type and Status.
     */
    protected function documentFormSchema(): array
    {
        return [
            TextInput::make('lao_number')
                ->label('LAO/E/C/LO NO.'),

            Select::make('type_id')
                ->label('Type')
                ->options(fn () => DocumentType::pluck('name', 'type_id')->toArray() + ['others' => 'Others'])
                ->live()
                ->required(),

            TextInput::make('type_other')
                ->label('Specify type')
                ->visible(fn (Get $get): bool => $get('type_id') === 'others')
                ->required(fn (Get $get): bool => $get('type_id') === 'others'),

            Select::make('client_id')
                ->label('Client')
                ->options(fn () => Client::pluck('name', 'client_id'))
                ->searchable()
                ->required(),

            TextInput::make('office_unit')
                ->label('Office/Unit'),

            Textarea::make('particulars')
                ->label('Particulars')
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
                ->label('Specify status')
                ->visible(fn (Get $get): bool => $get('status') === 'others')
                ->required(fn (Get $get): bool => $get('status') === 'others'),

            DatePicker::make('deadline')
                ->label('Deadline'),
        ];
    }

    /**
     * Turns the "others" placeholder into real data before saving:
     * - a typed type_other becomes a new (or existing) document_types row
     * - a typed status_other is kept, otherwise cleared
     */
    protected function resolveOthers(array $data): array
    {
        if (($data['type_id'] ?? null) === 'others') {
            $type = DocumentType::firstOrCreate(['name' => $data['type_other']]);
            $data['type_id'] = $type->type_id;
        }
        unset($data['type_other']);

        if (($data['status'] ?? null) !== 'others') {
            $data['status_other'] = null;
        }

        return $data;
    }

    public function addDocumentAction(): Action
    {
        return Action::make('addDocument')
            ->label('Add Documents')
            ->modalHeading('Add new document')
            ->modalSubmitActionLabel('Save')
            ->form($this->documentFormSchema())
            ->extraModalFooterActions([
                Action::make('addToOutgoing')
                    ->label('Add to Outgoing')
                    ->color('success')
                    ->action(function (array $data): void {
                        $data['user_id'] = auth()->id();
                        $data['outgoing_date'] = now()->toDateString();
                        Document::create($this->resolveOthers($data));
                    }),
            ])
            ->action(function (array $data): void {
                $data['user_id'] = auth()->id();
                Document::create($this->resolveOthers($data));
            });
    }
}