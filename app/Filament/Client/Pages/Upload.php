<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema; 
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
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
                
                Select::make('office_unit_id')
                    ->label('Office From')
                    ->options(fn () => OfficeUnit::query()->orderBy('name')->pluck('name', 'office_unit_id'))
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required()->maxLength(255),
                        ColorPicker::make('color')->nullable(),
                    ])
                    ->createOptionUsing(fn (array $data): int => OfficeUnit::create($data)->getKey())
                    ->required(),

                TextInput::make('document_type')
                    ->label('Document Type')
                    ->datalist(fn () => DocumentType::query()->orderBy('type_name')->pluck('type_name'))
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

        DB::transaction(function () use ($data): void {
            $document = Document::create([
                'user_id' => auth()->id(),
                'particulars' => $data['particulars'],
                'office_unit_id' => $data['office_unit_id'],
                'document_type' => $data['document_type'],
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
