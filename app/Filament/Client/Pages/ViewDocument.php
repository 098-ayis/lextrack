<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use Filament\Pages\Page;

class ViewDocument extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'documents/{document}';

    protected string $view = 'filament.client.pages.view-document';

    public Document $documentRecord;

    public ?string $requestStatus = null;

    public ?string $previewUrl = null;

    public string $returnTab = 'all';

    public function mount($document): void
    {
        $tab = request()->query('tab', 'all');

        $this->returnTab = in_array($tab, [
            'all',
            'pending',
            'in_progress',
            'completed',
            'rejected',
            'requested',
        ], true) ? $tab : 'all';

        $this->documentRecord = Document::query()
            ->where('document_id', $document)
            ->where(function ($query) {
                $query
                    ->where('user_id', auth()->id())
                    ->orWhereHas(
                        'documentRequests',
                        fn ($requestQuery) => $requestQuery
                            ->where('user_id', auth()->id())
                    );
            })
            ->with([
                'type',
                'latestVersion',
                'rejections' => fn ($query) => $query
                    ->latest('created_at')
                    ->latest('rejected_id'),
            ])
            ->firstOrFail();

        $this->requestStatus = $this->documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->latest('created_at')
            ->latest('request_id')
            ->value('status');

        $canAccessFile =
            (int) $this->documentRecord->user_id === (int) auth()->id()
            || $this->documentRecord
                ->documentRequests()
                ->where('user_id', auth()->id())
                ->where('status', 'accepted')
                ->exists();

        if (
            $canAccessFile &&
            $this->documentRecord->latestVersion?->file_path
        ) {
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
