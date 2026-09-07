<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema; 
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
use App\Services\AdminDocumentNotificationService;
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
                    ->datalist(fn () => OfficeUnit::query()->orderBy('name')->pluck('name'))
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
        $filePath = $data['file_path'];

        $document = DB::transaction(function () use ($data, $filePath): Document {
            $document = Document::create([
                'user_id' => auth()->id(),
                'particulars' => $data['particulars'],
                'document_name' => basename((string) $filePath),
                'office_unit' => $data['office_unit'],
                'document_type' => $data['document_type'],
                'status' => 'pending',
            ]);

            DocumentVersion::create([
                'user_id' => auth()->id(),
                'document_id' => $document->document_id,
                'version_number' => '1',
                'file_path' => $filePath,
            ]);

            return $document;
        });

        app(AdminDocumentNotificationService::class)
            ->notifyDocumentSubmitted($document);

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
