<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use Filament\Pages\Page;

class Documents extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Document Details';

    protected static ?string $slug = 'documents/{document}';

    protected string $view = 'filament.client.pages.documents';

    public Document $documentRecord;

    public function mount(int $document): void
    {
        $this->documentRecord = Document::query()
            ->where('document_id', $document)
            ->where('user_id', auth()->id())
            ->with('latestVersion')
            ->firstOrFail();
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        $previewUrl = null;

        if ($this->documentRecord->latestVersion?->file_path) {
            $previewUrl = route(
                'client.document.preview',
                ['document' => $this->documentRecord->document_id]
            );
        }

        return [
            'documentRecord' => $this->documentRecord,
            'previewUrl' => $previewUrl,
        ];
    }
}
