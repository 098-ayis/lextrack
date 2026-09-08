<x-filament-panels::page>
    <style>
        .report-panel { background:var(--report-bg,#fff); border:1px solid #e5e7eb; border-radius:12px; padding:20px; }
        .report-filters { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; align-items:end; }
        .report-field { display:grid; gap:7px; font-size:13px; color:#64748b; }
        .report-field input,.report-field select { width:100%; min-width:0; border:1px solid #d1d5db; border-radius:7px; padding:9px 11px; background:var(--report-bg,#fff); color:inherit; }
        .report-table-wrap { overflow-x:auto; }
        .report-table { width:100%; text-align:left; border-collapse:collapse; font-size:13px; }
        .report-table th { background:#f8fafc; color:#64748b; font-weight:500; white-space:nowrap; }
        .report-table th,.report-table td { padding:16px; border-bottom:1px solid #edf0f4; vertical-align:top; }
        .report-table td { min-width:105px; }
        .report-muted { color:#64748b; font-size:12px; }
        .report-heading { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; }
        .report-heading h2 { font-size:20px; font-weight:600; }
        .report-cards { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .report-number { font-size:30px; font-weight:650; margin:16px 0; color:#7c3aed; }
        .report-badge { display:inline-block; padding:4px 9px; border-radius:6px; background:#f3e8ff; color:#7e22ce; white-space:nowrap; }
        .report-template { display:flex; flex-wrap:wrap; gap:20px; justify-content:space-between; align-items:end; }
        .report-generate-form { display:flex; flex-wrap:wrap; align-items:flex-end; gap:16px; }
        .report-generate-form input,.report-generate-form button { height:48px; box-sizing:border-box; }
        .dark .report-panel { --report-bg:#18181b; border-color:#3f3f46; }
        .dark .report-table th { background:#27272a; }
        .dark .report-table td,.dark .report-table th { border-color:#3f3f46; }
        @media(min-width:1100px) { .report-filters { grid-template-columns:repeat(6,minmax(0,1fr)) auto; } .report-cards { grid-template-columns:repeat(4,minmax(0,1fr)); } }
        @media(max-width:500px) { .report-filters,.report-cards { grid-template-columns:1fr; } }
    </style>

    <div class="report-panel">
        <form wire:submit="applyFilters" class="report-filters">
            <label class="report-field">From<input type="date" wire:model="from" required>@error('from') <span role="alert">{{ $message }}</span> @enderror</label>
            <label class="report-field">To<input type="date" wire:model="to" required>@error('to') <span role="alert">{{ $message }}</span> @enderror</label>
            <label class="report-field">Document type<select wire:model="type"><option value="">All types</option>@foreach($types as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></label>
            <label class="report-field">Office / Unit<select wire:model="office"><option value="">All offices</option>@foreach($offices as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></label>
            <label class="report-field">Status<select wire:model="status"><option value="">All statuses</option>@foreach(\App\Filament\Pages\Reports::STATUSES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
            <label class="report-field">Document<input type="search" wire:model="search" placeholder="LAO no. or particulars" maxlength="255"></label>
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="applyFilters">Search</x-filament::button>
        </form>
        <p class="report-muted" style="margin-top:12px">Filter by upload date. Status reflects each document’s current status.</p>
    </div>

    <div class="report-panel" style="padding:0" wire:loading.class="opacity-50">
        <div class="report-table-wrap">
            <table class="report-table">
                <thead><tr><th>Date uploaded</th><th>LAO number</th><th>Particulars</th><th>Document type</th><th>Office / Unit</th><th>Uploaded by</th><th>Action taken</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr wire:key="report-document-{{ $document->document_id }}">
                            <td>{{ $document->created_at?->format('M d, Y') }}</td>
                            <td><strong>{{ $document->lao_number ?? '—' }}</strong></td>
                            <td style="min-width:220px">{{ $document->particulars ?? '—' }}</td>
                            <td>{{ $document->document_type ?? '—' }}</td><td>{{ $document->office_unit ?? '—' }}</td>
                            <td>{{ $document->user?->name ?? '—' }}</td><td>{{ $document->action_type ?? '—' }}</td>
                            <td><span class="report-badge">{{ \App\Filament\Pages\Reports::STATUSES[$document->status] ?? $document->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;padding:40px">No documents match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px">{{ $documents->links() }}</div>
    </div>

    <div class="report-heading"><h2>Totals</h2><span class="report-muted">Across all matching documents</span></div>
    <div class="report-cards">
        <div class="report-panel"><h3>Total documents</h3><div class="report-number">{{ $total }}</div><p class="report-muted">Uploaded in the selected period</p></div>
        @foreach(['in_progress' => 'Incoming', 'outgoing' => 'Outgoing', 'completed' => 'Completed'] as $key => $label)
            <div class="report-panel"><h3>{{ $label }}</h3><div class="report-number">{{ $counts[$key] ?? 0 }}</div><p class="report-muted">{{ $total > 0 ? round(($counts[$key] ?? 0) / $total * 100) : 0 }}% of matching documents</p></div>
        @endforeach
    </div>

    <section class="report-panel report-template" aria-labelledby="template-title">
        <div><h2 id="template-title" style="font-size:17px;font-weight:600">Monthly accomplishment report</h2><p class="report-muted" style="margin-top:6px">Generate the official report with the university letterhead and template.</p><p class="report-muted">Uses all processing activity for the reporting month selected below.</p></div>
        <form method="GET" action="{{ route('admin.reports.monthly') }}" target="_blank" class="report-generate-form">
            <label class="report-field">Reporting month<input type="month" name="month" value="{{ now()->format('Y-m') }}" required></label>
            <x-filament::button type="submit" icon="heroicon-o-document-chart-bar">Generate Report</x-filament::button>
        </form>
    </section>
</x-filament-panels::page>
