<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\Width;
use App\Models\Document;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;


class Outgoing extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 4;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected string $view = 'filament.pages.outgoing';

    public string $search = '';

    public string $typeFilter = '';

    public int $perPage = 10;

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

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function archiveDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);

        $document->update([
            'status' => 'archived',
        ]);
    }

    public function editDocumentAction(): Action
    {
        return Action::make('editDocument')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->tooltip('Edit Document')

            ->extraAttributes([
                'class' => 'edit-document-button',
            ])

            ->modalHeading('Edit Outgoing Document')

            ->schema([

                TextInput::make('lao_number')
                    ->label('LAO Number')
                    ->required(),

                Select::make('type_id')
                    ->label('Document Type')
                    ->placeholder('Select document type')
                    ->options(
                        \App\Models\DocumentType::query()
                            ->orderBy('type_name')
                            ->pluck('type_name', 'type_id')
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('office_unit')
                    ->label('Office / Unit')
                    ->required(),

                Textarea::make('particulars')
                    ->label('Particulars')
                    ->required(),

                DatePicker::make('outgoing_date')
                    ->label('Outgoing Date'),

                TextInput::make('sent_to')
                    ->label('Sent To')
                    ->placeholder('Enter office / person sent to'),

                DatePicker::make('sent_date')
                    ->label('Sent Date'),

                TextInput::make('returned_from')
                    ->label('Returned From')
                    ->placeholder('Enter office / person returned from'),

                DatePicker::make('date_returned')
                    ->label('Returned Date'),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->disk('public')
                    ->directory('documents')
                    ->preserveFilenames(),

            ])

            ->fillForm(function (array $arguments): array {

                $document = Document::findOrFail(
                    $arguments['document']
                );

                return [
                    'lao_number'     => $document->lao_number,
                    'type_id'        => $document->type_id,
                    'office_unit'    => $document->office_unit,
                    'particulars'    => $document->particulars,

                    'outgoing_date'  => $document->outgoing_date,
                    'sent_to'        => $document->sent_to,
                    'sent_date'      => $document->sent_date,
                    'returned_from'  => $document->returned_from,
                    'date_returned'  => $document->date_returned,

                    'file_path'      => $document->file_path,
                ];
            })

            ->action(function (
                array $data,
                array $arguments
            ): void {

                $document = Document::findOrFail(
                    $arguments['document']
                );

                $document->update($data);
            });
    }

    

    public function getDocuments()
    {
        return Document::query()
            ->with(['user', 'type', 'actionType'])
            ->where('status', 'outgoing')

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
}