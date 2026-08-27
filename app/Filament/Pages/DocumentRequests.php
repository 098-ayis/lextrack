<?php

namespace App\Filament\Pages;

use App\Models\DocumentRequest;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\WithPagination;

class DocumentRequests extends Page
{
    use WithPagination;

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Requests';

    protected static ?string $title = 'Document Requests';

    protected string $view = 'filament.pages.document-requests';

    public string $activeSection = 'pending';

    public string $search = '';

    public string $typeFilter = '';

    public int $perPage = 10;

    public function mount(): void
    {
        $section = request()->query('section', 'pending');

        $this->activeSection = in_array($section, [
            'pending',
            'accepted',
            'rejected',
        ], true) ? $section : 'pending';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getStatusCounts(): array
    {
        $counts = DocumentRequest::query()
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'accepted' => (int) ($counts['accepted'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    public function getDocumentRequests(string $section = 'pending')
    {
        $status = match ($section) {
            'accepted' => 'accepted',
            'rejected' => 'rejected',
            default => 'pending',
        };

        return DocumentRequest::query()
            ->with(['document.type', 'user'])
            ->where('status', $status)
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search) {
                    $query
                        ->whereHas('document', function ($query) use ($search) {
                            $query
                                ->where('lao_number', 'like', $search)
                                ->orWhere('office_unit', 'like', $search)
                                ->orWhere('particulars', 'like', $search);
                        })
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->when($this->typeFilter, function ($query) {
                $query->whereHas('document', function ($query) {
                    $query->where('type_id', $this->typeFilter);
                });
            })
            ->latest('date_of_request')
            ->latest('request_id')
            ->paginate($this->perPage);
    }

    public function acceptRequest(int $requestId): void
    {
        DocumentRequest::findOrFail($requestId)->update([
            'status' => 'accepted',
            'date_processed' => now()->toDateString(),
        ]);

        $this->redirect(self::getUrl(['section' => 'accepted']));
    }

    public function rejectRequest(int $requestId): void
    {
        DocumentRequest::findOrFail($requestId)->update([
            'status' => 'rejected',
            'date_processed' => now()->toDateString(),
        ]);

        $this->redirect(self::getUrl(['section' => 'rejected']));
    }

    public function returnRequest(int $requestId): void
    {
        DocumentRequest::findOrFail($requestId)->update([
            'status' => 'pending',
            'date_processed' => null,
        ]);

        $this->redirect(self::getUrl(['section' => 'pending']));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
}
