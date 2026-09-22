<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use App\Models\DocumentRequest;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ViewDocument extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'documents/{document}';

    protected string $view = 'filament.client.pages.view-document';

    public Document $documentRecord;

    public ?string $requestStatus = null;

    public ?DocumentRequest $requestRecord = null;

    public ?string $previewUrl = null;

    public ?string $downloadUrl = null;

    public string $returnTab = 'all';

    public string $returnPage = 'documents';

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function mount($document): void
    {
        $id = (string) $document;

        $this->returnPage = request()->query('from') === 'dashboard'
            ? 'dashboard'
            : 'documents';

        $tab = request()->query('tab');

        $hasValidReturnTab = in_array($tab, [
            'all',
            'pending',
            'in_progress',
            'completed',
            'rejected',
            'requested',
        ], true);

        $this->returnTab = $hasValidReturnTab ? $tab : 'all';

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
                'latestVersion',
                'activityLogs' => fn ($query) => $query
                    ->oldest('created_at')
                    ->oldest('log_id'),
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

        $this->requestRecord = $this->documentRecord
            ->documentRequests()
            ->with('user')
            ->where('user_id', auth()->id())
            ->latest('date_of_request')
            ->latest('request_id')
            ->first();

        if (! $hasValidReturnTab && $this->requestStatus !== null) {
            $this->returnTab = 'requested';
        }

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
                'document' => $this->documentRecord->getPublicRouteKey(),
            ]);

            $this->downloadUrl = route('client.document.download', [
                'document' => $this->documentRecord->getPublicRouteKey(),
            ]);
        }
    }

    public function getHeading(): string
    {
        return '';
    }
}
