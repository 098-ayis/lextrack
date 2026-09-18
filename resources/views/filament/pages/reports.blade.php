<x-filament-panels::page>
    <style>
        .report-panel { background:var(--report-bg,#fff); border:1px solid #e5e7eb; border-radius:12px; padding:20px; }
        .report-filters { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; align-items:end; }
        .report-field { display:grid; gap:7px; font-size:13px; color:#64748b; }
        .report-field input,.report-field select { width:100%; min-width:0; border:1px solid #d1d5db; border-radius:7px; padding:9px 11px; background:var(--report-bg,#fff); color:inherit; }
        .report-table-wrap { width:100%; min-width:0; }
        .report-table { width:100%; text-align:left; border-collapse:collapse; table-layout:fixed; font-size:13px; }
        .report-table th { background:#f8fafc; color:#111827; font-weight:600; text-transform:uppercase; white-space:normal; font-size:12px; }
        .report-table th,.report-table td { padding:12px 10px; overflow-wrap:anywhere; border-bottom:1px solid #edf0f4; vertical-align:top; }
        .report-table td { min-width:0; }
        @media(min-width:761px) { .report-table th:first-child,.report-table td:first-child:not([colspan]) { padding-left:24px; } }
        .report-muted { color:#64748b; font-size:12px; }
        .report-heading { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; }
        .report-heading h2 { font-size:20px; font-weight:600; }
        .report-cards { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .report-number { font-size:30px; font-weight:650; margin:16px 0; color:#6366f1; }
        .report-badge { display:inline-block; padding:4px 9px; border-radius:6px; background:#f3f4f6; color:#4b5563; white-space:normal; }
        .report-badge[data-status="completed"] { background:#dcfce7; color:#15803d; }
        .report-badge[data-status="in_progress"] { background:#dbeafe; color:#1d4ed8; }
        .report-badge[data-status="pending"] { background:#fef3c7; color:#92400e; }
        .report-badge[data-status="outgoing"] { background:#ffedd5; color:#c2410c; }
        .report-badge[data-status="rejected"] { background:#fee2e2; color:#b91c1c; }
        .dark .report-badge { background:#374151; color:#d1d5db; }
        .dark .report-badge[data-status="completed"] { background:#14532d; color:#bbf7d0; }
        .dark .report-badge[data-status="in_progress"] { background:#1e3a8a; color:#bfdbfe; }
        .dark .report-badge[data-status="pending"] { background:#78350f; color:#fde68a; }
        .dark .report-badge[data-status="outgoing"] { background:#7c2d12; color:#fed7aa; }
        .dark .report-badge[data-status="rejected"] { background:#7f1d1d; color:#fecaca; }
        .report-template { display:flex; flex-wrap:wrap; gap:20px; justify-content:space-between; align-items:end; }
        .report-generate-form { display:flex; flex-wrap:wrap; align-items:flex-end; gap:16px; }
        .report-generate-form input,.report-generate-form button { height:48px; box-sizing:border-box; }
        .dark .report-panel { --report-bg:#18181b; border-color:#3f3f46; }
        .dark .report-table th { background:#27272a; color:#e5e7eb; }
        .dark .report-table td,.dark .report-table th { border-color:#3f3f46; }
        @media(min-width:1100px) { .report-filters { grid-template-columns:repeat(6,minmax(0,1fr)) auto; } .report-cards { grid-template-columns:repeat(4,minmax(0,1fr)); } }
        @media(max-width:760px) {
            .report-table colgroup,.report-table thead { display:none; }
            .report-table,.report-table tbody,.report-table tr { display:block; width:100%; }
            .report-table tr { border-bottom:1px solid #e5e7eb; padding:8px 0; }
            .report-table td { display:grid; grid-template-columns:minmax(0,40%) minmax(0,1fr); gap:12px; border:0; padding:7px 12px; }
            .report-table td[data-label]::before { content:attr(data-label); font-size:11px; font-weight:600; text-transform:uppercase; color:#64748b; }
            .report-table td[colspan] { display:block; }
        }
        @media(max-width:500px) { .report-filters,.report-cards { grid-template-columns:1fr; } }
    </style>

    <div class="report-cards">
        <div class="report-panel"><h3>Total documents</h3><div class="report-number">{{ $total }}</div></div>
        @foreach(['in_progress' => 'Incoming', 'outgoing' => 'Outgoing', 'completed' => 'Completed'] as $key => $label)
            <div class="report-panel"><h3>{{ $label }}</h3><div class="report-number">{{ $counts[$key] ?? 0 }}</div></div>
        @endforeach
    </div>

    <section class="report-panel report-template" aria-labelledby="template-title">
        <div><h2 id="template-title" style="font-size:17px;font-weight:600">Monthly Accomplishment Report</h2></div>
        <form method="GET" action="{{ route('admin.reports.monthly') }}" target="_blank" class="report-generate-form">
            <label class="report-field"><input type="month" aria-label="Reporting month" name="month" value="{{ now()->format('Y-m') }}" required></label>
            <x-filament::button type="submit" icon="heroicon-o-document-chart-bar">Generate Report</x-filament::button>
        </form>
    </section>

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
    </div>

    <div class="report-panel" style="padding:0" wire:loading.class="opacity-50">
        <div class="report-table-wrap">
            <table class="report-table">
                <colgroup><col style="width:10%"><col style="width:9%"><col style="width:17%"><col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:11%"><col style="width:10%"><col style="width:13%"></colgroup>
                <thead><tr><th>Date uploaded</th><th>LAO number</th><th>Particulars</th><th>Document type</th><th>Office / Unit</th><th>Uploaded by</th><th>Action taken</th><th>Status</th><th>Date accomplished</th></tr></thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr wire:key="report-document-{{ $document->document_id }}">
                            <td data-label="Date uploaded">{{ $document->created_at?->format('M d, Y') }}</td>
                            <td data-label="LAO number"><strong>{{ $document->lao_number ?? '—' }}</strong></td>
                            <td data-label="Particulars">{{ $document->particulars ?? '—' }}</td>
                            <td data-label="Document type">{{ $document->document_type ?? '—' }}</td><td data-label="Office / Unit">{{ $document->office_unit ?? '—' }}</td>
                            <td data-label="Uploaded by">{{ $document->user?->name ?? '—' }}</td><td data-label="Action taken">{{ $document->action_type ?? '—' }}</td>
                            <td data-label="Status"><span class="report-badge" data-status="{{ $document->status }}">{{ \App\Filament\Pages\Reports::STATUSES[$document->status] ?? $document->status }}</span></td>
                            <td data-label="Date accomplished">{{ $document->date_accomplished?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="text-align:center;padding:40px">No documents match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px">{{ $documents->links() }}</div>
    </div>

</x-filament-panels::page>
