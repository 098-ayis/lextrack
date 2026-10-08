<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use App\Models\DocumentView;
use App\Services\DocumentStatusTimeline;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class DocumentTimeline extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'documents/{document}/timeline';

    protected string $view = 'filament.client.pages.document-timeline';

    public Document $documentRecord;

    /**
     * @var array<int, array{status: string, title: string, description: string, time: string, date: string, color: string}>
     */
    public array $statusTimeline = [];

    public string $returnTab = 'all';

    public function getMaxContentWidth(): Width
    {
        return Width::Large;
    }

    public function mount($document): void
    {
        $id = (string) $document;
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
            ->where(function ($query) use ($id): void {
                $query->where('public_id', $id);

                if (ctype_digit($id)) {
                    $query->orWhereKey((int) $id);
                }
            })
            ->where(function ($query): void {
                $query
                    ->where('user_id', auth()->id())
                    ->orWhereHas(
                        'documentRequests',
                        fn ($requestQuery) => $requestQuery
                            ->where('user_id', auth()->id())
                    );
            })
            ->with([
                'conversation.messages' => fn ($query) => $query
                    ->where('sender_id', auth()->id())
                    // Message bodies are encrypted; match revision text after hydration.
                    ->oldest('created_at')
                    ->oldest('id'),
                'activityLogs' => fn ($query) => $query
                    ->oldest('created_at')
                    ->oldest('log_id'),
            ])
            ->firstOrFail();

        DocumentView::updateOrCreate(
            [
                'document_id' => $this->documentRecord->document_id,
                'user_id' => auth()->id(),
            ],
            [
                'viewed_at' => now(),
            ]
        );

        $this->statusTimeline = array_values(array_reverse(
            app(DocumentStatusTimeline::class)
                ->build($this->documentRecord, includeClientRevisions: true),
        ));
    }

    public function getHeading(): string
    {
        return '';
    }
}
