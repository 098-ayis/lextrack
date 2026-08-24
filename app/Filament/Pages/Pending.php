<?php

namespace App\Filament\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\WithPagination;

class Pending extends Page
{
    protected static ?int $navigationSort = 2;

    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.pending';


    protected $fillable = [
    'user_id',
    'type_id',
    'action_id',
    'lao_number',
    'office_unit',
    'particulars',
    'deadline' => 'date',
    'sent_to',
    'sent_date',
    'returned_from',
    'date_returned',
    'outgoing_date',
    'status',
    'file_path',
    ];

    public string $search = '';

    public string $typeFilter = '';

    public int $perPage = 10;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getDocuments()
    {
        return Document::query()
            ->with(['user', 'type', 'actionType'])
            ->where('status', 'pending')

            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('lao_number', 'like', $search)
                        ->orWhere('office_unit', 'like', $search)
                        ->orWhere('particulars', 'like', $search)
                        ->orWhere('sent_to', 'like', $search)
                        ->orWhere('returned_from', 'like', $search);
                });
            })

            ->when($this->typeFilter, function ($query) {
                $query->where('type_id', $this->typeFilter);
            })

            ->latest('outgoing_date')
            ->paginate($this->perPage);
    }

    public function acceptDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);

        $document->update([
            'status' => 'in_progress',
        ]);
    }

    public function rejectDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);

        $document->update([
            'status' => 'rejected',
        ]);
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