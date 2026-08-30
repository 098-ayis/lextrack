<?php

namespace App\Filament\Client\Widgets;

use App\Filament\Client\Pages\ViewDocument;
use App\Models\Document;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class DashboardDocumentTable extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.client.widgets.dashboard-document-table';

    public string $documentSearch = '';

    public string $documentType = '';

    public string $documentStatus = '';

    public function clearSearch(): void
    {
        $this->documentSearch = '';
    }

    public function clearType(): void
    {
        $this->documentType = '';
    }

    public function clearStatus(): void
    {
        $this->documentStatus = '';
    }

    public function getDocuments(): \Illuminate\Support\Collection
    {
        return $this->documentsQuery()
            ->with([
                'type',
                'latestVersion',
                'documentRequests' => fn ($query) => $query
                    ->where('user_id', auth()->id())
                    ->latest('created_at')
                    ->latest('request_id'),
            ])
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    protected function documentsQuery(): Builder
    {
        return Document::query()
            ->where(function (Builder $query): void {
                $query
                    ->where('user_id', auth()->id())
                    ->orWhereHas(
                        'documentRequests',
                        fn (Builder $requestQuery) => $requestQuery
                            ->where('user_id', auth()->id())
                    );
            })
            ->when(
                $this->documentType !== '',
                fn (Builder $query) => $query->where(
                    'type_id',
                    (int) $this->documentType
                )
            )
            ->when(
                $this->documentStatus !== '',
                function (Builder $query): void {
                    if ($this->documentStatus === 'in_progress') {
                        $query->whereIn('status', [
                            'in_progress',
                            'outgoing',
                        ]);

                        return;
                    }

                    if ($this->documentStatus === 'completed') {
                        $query->whereIn('status', [
                            'completed',
                            'archived',
                        ]);

                        return;
                    }

                    $query->where('status', $this->documentStatus);
                }
            )
            ->when(
                trim($this->documentSearch) !== '',
                function (Builder $query): void {
                    $search = trim($this->documentSearch);

                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('particulars', 'like', "%{$search}%")
                            ->orWhere('office_unit', 'like', "%{$search}%")
                            ->orWhere('lao_number', 'like', "%{$search}%");
                    });
                }
            );
    }
}
