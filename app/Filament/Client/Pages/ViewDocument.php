<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ViewDocument extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'documents/{document}';

    protected string $view = 'filament.client.pages.view-document';

    public Document $documentRecord;

    public ?string $previewUrl = null;

    public function mount($document): void
    {
        $this->documentRecord = Document::query()
            ->where('document_id', $document)
            ->where('user_id', auth()->id())
            ->with(['type', 'latestVersion'])
            ->firstOrFail();

        if ($this->documentRecord->latestVersion?->file_path) {
            $this->previewUrl = route('client.document.preview', [
                'document' => $this->documentRecord->document_id,
            ]);
        }
    }

    public function getHeading(): string
    {
        return '';
    }
}
