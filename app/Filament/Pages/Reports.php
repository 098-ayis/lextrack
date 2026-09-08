<?php

namespace App\Filament\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Livewire\WithPagination;

class Reports extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.reports';

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
        $query = Document::query()
            ->whereDate('created_at', '>=', $this->from)
            ->whereDate('created_at', '<=', $this->to)
            ->when($this->type !== '', fn ($q) => $q->where('document_type', $this->type))
            ->when($this->office !== '', fn ($q) => $q->where('office_unit', $this->office))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($q) => $q->where('lao_number', 'like', $term)->orWhere('particulars', 'like', $term));
            });
        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'documents' => (clone $query)->with('user')->latest('created_at')->orderByDesc('document_id')->paginate(15),
            'counts' => $counts,
            'total' => $counts->sum(),
            'types' => Document::whereNotNull('document_type')->distinct()->orderBy('document_type')->pluck('document_type'),
            'offices' => Document::whereNotNull('office_unit')->distinct()->orderBy('office_unit')->pluck('office_unit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
