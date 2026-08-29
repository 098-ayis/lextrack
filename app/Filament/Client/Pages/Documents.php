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
            ->firstOrFail();
    }

    public function getHeading(): string
    {
        return $table
            ->query(
                Document::query()
                    ->where('user_id', auth()->id())
                    ->when($this->activeTab !== 'all', function ($query) {
                        if ($this->activeTab === 'in_progress') {
                            $query->whereIn('status', [
                                'in_progress',
                                'outgoing',
                            ]);

                            return;
                        }

                        if ($this->activeTab === 'completed') {
                            $query->whereIn('status', [
                                'completed',
                                'archived',
                            ]);

                            return;
                        }

                        $query->where('status', $this->activeTab);
                    })
                    ->latest()
            )
            ->columns([
                TextColumn::make('id')->label('LAO #'),
                TextColumn::make('particulars')->label('PARTICULARS'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match (strtolower((string) $state)) {
                            'archived' => 'Completed',
                            'outgoing' => 'In Progress',
                            default => ucwords(str_replace('_', ' ', (string) $state)),
                        }
                    ),
            ]);
    }

    public function getViewData(): array
    {
        $previewUrl = null;

        if ($this->documentRecord->file_path) {
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