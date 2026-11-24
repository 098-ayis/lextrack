<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
use App\Support\RoleSecurity;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Livewire\WithPagination;
use UnitEnum;

class Reports extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 3;

    protected static string|UnitEnum|null $navigationGroup = 'MANAGEMENT';

    protected string $view = 'filament.pages.reports';

    protected \Filament\Support\Enums\Width|string|null $maxContentWidth = Width::Full;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(RoleSecurity::LEGAL_STAFF) ?? false;
    }

    public string $from = '';

    public string $to = '';

    public string $type = '';

    public string $office = '';

    public string $status = '';

    public string $search = '';

    public const STATUSES = [
        'pending' => 'Pending', 'in_progress' => 'Incoming', 'outgoing' => 'Outgoing',
        'completed' => 'Completed', 'rejected' => 'Rejected', 'archived' => 'Archived',
    ];

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function applyFilters(): void
    {
        $this->validate([
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            'search' => 'string|max:255',
            'type' => 'string|max:255',
            'office' => 'string|max:255',
            'status' => 'nullable|in:'.implode(',', array_keys(self::STATUSES)),
        ]);
        $this->resetPage();
    }

    protected function getViewData(): array
    {
        $query = $this->filteredDocumentsQuery();
        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'documents' => (clone $query)->with(['user', 'activityLogs', 'documentType', 'officeUnit', 'actionType'])->latest('created_at')->orderByDesc('document_id')->paginate(15),
            'counts' => $counts,
            'total' => $counts->sum(),
            'types' => Schema::hasTable('document_types')
                ? DocumentType::query()->orderBy('type_name')->pluck('type_name')
                : Document::query()->whereNotNull('document_type')->distinct()->orderBy('document_type')->pluck('document_type'),
            'offices' => Schema::hasTable('office_units')
                ? OfficeUnit::query()->orderBy('name')->pluck('name')
                : Document::query()->whereNotNull('office_unit')->distinct()->orderBy('office_unit')->pluck('office_unit'),
        ];
    }

    private function filteredDocumentsQuery(): Builder
    {
        $search = trim($this->search);

        return Document::query()
            ->whereDate('created_at', '>=', $this->from)
            ->whereDate('created_at', '<=', $this->to)
            ->when(filled($this->type), fn (Builder $query) => $query->forDocumentType($this->type))
            ->when(filled($this->office), fn (Builder $query) => $query->forOfficeUnit($this->office))
            ->when(filled($this->status), fn (Builder $query) => $query->where('status', $this->status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('lao_number', 'like', $term)
                        ->orWhere('particulars', 'like', $term);
                });
            });
    }
}
