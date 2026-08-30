<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema; 
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use App\Models\Document; 
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;

class Upload extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.client.pages.upload';
    
    protected static ?string $navigationLabel = 'Upload';
    
    protected static ?string $title = 'Upload Document';

    public function getHeading(): string
    {
        return '';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('particulars')
                    ->label('Document Name')
                    ->columnSpan('full')
                    ->required(),
                
                TextInput::make('office_unit')
                    ->label('Office From')
                    ->placeholder('e.g., College of Science')
                    ->required(),

                Select::make('type_id')
                    ->label('Document Type')
                    ->options([
                        1 => 'MOA',
                        2 => 'Correspondence',
                        3 => 'Contract',
                        4 => 'Proposal',
                        5 => 'PROCUREMENT',
                        6 => 'REFERENCE SLIP',
                        7 => 'Clearance',
                        8 => 'MOU',
                        9 => 'NDA',
                        10 => 'DOD',
                        11 => 'GBA',
                        12 => 'Others',
                    ])
                    ->searchable()
                    ->live()
                    ->required(),

                TextInput::make('other_type')
                    ->label('Please specify Document Type')
                    ->placeholder('e.g., Affidavit')
                    ->visible(fn (Get $get) => $get('type_id') == 12)
                    ->required(),
                    
                FileUpload::make('file_path')
                    ->label('Upload Document')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(5120)
                    ->disk('local')
                    ->directory('client-documents')
                    ->preserveFilenames()
                    ->columnSpan('full')
                    ->required(),
                            ])
                            ->columns(2)
                            ->statePath('data');
                    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $finalParticulars = $data['particulars'];
        if ($data['type_id'] == 12 && !empty($data['other_type'])) {
            $finalParticulars = '[' . $data['other_type'] . '] ' . $finalParticulars;
        }

        DB::transaction(function () use ($finalParticulars, $data): void {
            $document = Document::create([
                'user_id' => auth()->id(),
                'particulars' => $finalParticulars,
                'office_unit' => $data['office_unit'],
                'type_id' => $data['type_id'],
                'status' => 'pending',
            ]);

            DocumentVersion::create([
                'user_id' => auth()->id(),
                'document_id' => $document->document_id,
                'version_number' => '1',
                'file_path' => $data['file_path'],
            ]);
        });

        Notification::make()
            ->title('Document submitted successfully!')
            ->success()
            ->send();

        $this->form->fill(); 
    }

    public function clearForm(): void
    {
        $this->form->fill();
    }
}
