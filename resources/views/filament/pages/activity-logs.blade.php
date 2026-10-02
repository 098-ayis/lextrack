<x-filament-panels::page>
    @php
        $selectedPresentation = $selectedLog ? $this->presentLog($selectedLog) : null;
    @endphp

    <div class="activity-logs-page">
        <div class="activity-logs-heading">
            <div>
                <h1>Activity Logs</h1>
            </div>
            <x-filament::button type="button" wire:click="export" icon="heroicon-o-arrow-down-tray" wire:loading.attr="disabled" wire:target="export">
                Export
            </x-filament::button>
        </div>

        <section class="activity-logs-filter-card" aria-label="Activity log filters">
            <div>
                <div class="activity-logs-filter-heading">
                    <div class="activity-logs-filter-title">
                        <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-5 w-5" />
                        <span>Filters</span>
                    </div>
                    <button type="button" wire:click="clearFilters" class="activity-logs-clear-button">Clear</button>
                </div>

                <div class="activity-logs-filter-grid">
                    <label class="activity-logs-field activity-logs-search-field">
                        <span>Search</span>
                        <div class="activity-logs-search-wrap">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4" />
                            <input type="search" wire:model.live.debounce.300ms="search" maxlength="255" placeholder="Search actions or users">
                        </div>
                    </label>

                    <label class="activity-logs-field">
                        <span>Category</span>
                        <select wire:model.live="categoryFilter">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="activity-logs-field">
                        <span>Action</span>
                        <select wire:model.live="actionFilter">
                            <option value="">All actions</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}">{{ $action }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="activity-logs-field">
                        <span>Modified by</span>
                        <select wire:model.live="modifiedByFilter">
                            <option value="">Anyone</option>
                            @foreach ($modifiedUsers as $modifiedUser)
                                <option value="{{ $modifiedUser->id }}">{{ $modifiedUser->historical_display_name }}</option>
                            @endforeach
                        </select>
                    </label>

                </div>

                <div class="activity-logs-date-row">
                    <span class="activity-logs-date-label">Date</span>
                    <input type="date" wire:model.live="dateFrom" aria-label="Start date">
                    <span class="activity-logs-date-to" aria-hidden="true">—</span>
                    <input type="date" wire:model.live="dateTo" aria-label="End date">
                </div>

            </div>
        </section>

        <section class="activity-logs-table-card" wire:loading.class="opacity-60">
            <div class="activity-logs-table-heading">
                <div>
                    <h2>Activity Log</h2>
                    <p>{{ number_format($logs->total()) }} activities found</p>
                </div>
                <span class="activity-logs-privacy-note">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4" />
                    Confidential record contents hidden
                </span>
            </div>

            <div class="activity-logs-table-wrap">
                <table class="activity-logs-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Modified By</th>
                            <th>Date of Change</th>
                            <th>Subject</th>
                            <th class="activity-logs-actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php($presentation = $this->presentLog($log))
                            <tr wire:key="activity-log-{{ $log->log_id }}">
                                <td data-label="Category">{{ $presentation['category'] }}</td>
                                <td data-label="Action">
                                    <span class="activity-logs-action-badge {{ $presentation['action_class'] }}">{{ $presentation['action'] }}</span>
                                </td>
                                <td data-label="Description">{{ $presentation['description'] }}</td>
                                <td data-label="Modified By">
                                    <div class="activity-logs-user-cell">
                                        @if ($presentation['modified_photo'])
                                            <img src="{{ $presentation['modified_photo'] }}" alt="{{ $presentation['modified_by'] }}" referrerpolicy="no-referrer" class="activity-logs-avatar activity-logs-avatar-image">
                                        @else
                                            <span class="activity-logs-avatar">{{ strtoupper(substr($presentation['modified_by'], 0, 1)) }}</span>
                                        @endif
                                        <span>{{ $presentation['modified_by'] }}</span>
                                    </div>
                                </td>
                                <td data-label="Date of Change">
                                    <span class="activity-logs-date">{{ $log->created_at?->format('d M Y') ?? '—' }}</span>
                                    <small>{{ $log->created_at?->format('g:i A') ?? '' }}</small>
                                </td>
                                <td data-label="Subject">{{ $presentation['subject'] }}</td>
                                <td data-label="Actions" class="activity-logs-actions-column">
                                    <button type="button" wire:click="openDetails({{ $log->log_id }})" class="activity-logs-details-button">
                                        View details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="activity-logs-empty">No activity matches the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="activity-logs-pagination">{{ $logs->links() }}</div>
        </section>
    </div>

    @if ($selectedPresentation)
        <div class="activity-logs-modal-backdrop" role="presentation" wire:click="closeDetails">
            <section class="activity-logs-modal" role="dialog" aria-modal="true" aria-labelledby="activity-log-details-title" wire:click.stop>
                <div class="activity-logs-modal-heading">
                    <div>
                        <p class="activity-logs-modal-eyebrow">Activity details</p>
                        <h2 id="activity-log-details-title">{{ $selectedPresentation['action'] }}</h2>
                    </div>
                    <button type="button" wire:click="closeDetails" class="activity-logs-modal-close" aria-label="Close details">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="activity-logs-actor-card">
                    @if ($selectedPresentation['modified_photo'])
                        <img src="{{ $selectedPresentation['modified_photo'] }}" alt="{{ $selectedPresentation['modified_by'] }}" referrerpolicy="no-referrer" class="activity-logs-actor-photo">
                    @else
                        <span class="activity-logs-actor-photo activity-logs-avatar">{{ strtoupper(substr($selectedPresentation['modified_by'], 0, 1)) }}</span>
                    @endif
                    <div>
                        <strong>{{ $selectedPresentation['modified_by'] }}</strong>
                        @if ($selectedPresentation['modified_email'])
                            <span>{{ $selectedPresentation['modified_email'] }}</span>
                        @endif
                    </div>
                </div>

                <dl class="activity-logs-details-grid">
                    <div><dt>Audit ID</dt><dd>#{{ $selectedLog->log_id }}</dd></div>
                    <div><dt>Category</dt><dd>{{ $selectedPresentation['category'] }}</dd></div>
                    <div><dt>Action</dt><dd>{{ $selectedPresentation['action'] }}</dd></div>
                    <div><dt>Description</dt><dd>{{ $selectedPresentation['description'] }}</dd></div>
                    <div><dt>Date of change</dt><dd>{{ $selectedLog?->created_at?->format('d M Y, g:i A') ?? '—' }}</dd></div>
                    <div><dt>Subject</dt><dd>{{ $selectedPresentation['subject'] }}</dd></div>
                    <div><dt>Access</dt><dd>Administrative metadata only</dd></div>
                </dl>

                <div class="activity-logs-modal-notice">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 shrink-0" />
                    <p>This log confirms that the action occurred. Confidential document contents, particulars, filenames, message bodies, and underlying record links are not shown to Super Admin.</p>
                </div>

                <div class="activity-logs-modal-footer">
                    <x-filament::button type="button" color="gray" wire:click="closeDetails">Close</x-filament::button>
                </div>
            </section>
        </div>
    @endif

    <style>
        .activity-logs-page { display: grid; gap: 1rem; }
        .activity-logs-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
        .activity-logs-heading h1, .activity-logs-table-heading h2 { margin: 0; color: #111827; font-size: 1.35rem; font-weight: 700; }
        .activity-logs-heading p, .activity-logs-table-heading p { margin: 0.25rem 0 0; color: #737b8c; font-size: 0.85rem; }
        .activity-logs-filter-card, .activity-logs-table-card { border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #fff; box-shadow: 0 1px 2px rgb(15 23 42 / 0.04); }
        .activity-logs-filter-card { padding: 1rem; }
        .activity-logs-filter-heading, .activity-logs-table-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .activity-logs-filter-title { display: flex; align-items: center; gap: 0.45rem; color: #111827; font-size: 0.95rem; font-weight: 650; }
        .activity-logs-clear-button { border: 0; background: transparent; color: #6366f1; font-size: 0.8rem; font-weight: 600; cursor: pointer; }
        .activity-logs-filter-grid { display: grid; grid-template-columns: minmax(16rem, 2fr) repeat(3, minmax(10rem, 1fr)); gap: 0.75rem; align-items: end; margin-top: 1rem; }
        .activity-logs-field { display: grid; min-width: 0; gap: 0.35rem; color: #4b5563; font-size: 0.72rem; font-weight: 600; }
        .activity-logs-field select, .activity-logs-field input { width: 100%; min-width: 0; height: 2.35rem; border: 1px solid #cbd5e1; border-radius: 0.45rem; background: #fff; padding: 0.4rem 0.6rem; color: #1f2937; font-size: 0.78rem; font-weight: 400; }
        .activity-logs-field select:focus, .activity-logs-field input:focus { border-color: #6366f1; outline: 2px solid rgb(99 102 241 / 15%); }
        .activity-logs-search-wrap { position: relative; }
        .activity-logs-search-wrap svg { position: absolute; top: 50%; left: 0.65rem; color: #94a3b8; transform: translateY(-50%); }
        .activity-logs-search-wrap input { padding-left: 2rem; }
        .activity-logs-date-row { display: flex; flex-wrap: wrap; align-items: end; gap: 0.65rem; margin-top: 0.9rem; padding-top: 0.9rem; border-top: 1px solid #edf0f4; }
        .activity-logs-date-label { align-self: center; margin-right: 0.15rem; color: #475569; font-size: 0.78rem; font-weight: 650; }
        .activity-logs-date-row input { width: 10rem; height: 2.35rem; border: 1px solid #cbd5e1; border-radius: 0.45rem; background: #fff; padding: 0.4rem 0.6rem; color: #1f2937; font-size: 0.78rem; font-weight: 400; }
        .activity-logs-date-row input:focus { border-color: #6366f1; outline: 2px solid rgb(99 102 241 / 15%); }
        .activity-logs-date-to { align-self: center; padding-bottom: 0.05rem; color: #64748b; font-size: 0.78rem; }
        .activity-logs-table-card { overflow: hidden; }
        .activity-logs-table-heading { padding: 1rem 1.25rem; }
        .activity-logs-privacy-note { display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b; font-size: 0.72rem; }
        .activity-logs-table-wrap { width: 100%; overflow-x: auto; border-top: 1px solid #e2e8f0; }
        .activity-logs-table { width: 100%; min-width: 960px; border-collapse: collapse; text-align: left; font-size: 0.8rem; }
        .activity-logs-table th { background: #f8fafc; color: #475569; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; }
        .activity-logs-table th, .activity-logs-table td { border-bottom: 1px solid #edf0f4; padding: 0.85rem 0.9rem; vertical-align: middle; }
        .activity-logs-table tbody tr:hover { background: #fafbff; }
        .activity-logs-action-badge { display: inline-flex; border-radius: 999px; padding: 0.22rem 0.55rem; font-size: 0.7rem; font-weight: 650; white-space: nowrap; }
        .activity-logs-action-create { background: #dcfce7; color: #15803d; }
        .activity-logs-action-update { background: #dbeafe; color: #1d4ed8; }
        .activity-logs-action-delete { background: #fee2e2; color: #b91c1c; }
        .activity-logs-action-perform { background: #e0e7ff; color: #4338ca; }
        .activity-logs-user-cell { display: flex; min-width: 9rem; align-items: center; gap: 0.5rem; color: #1f2937; font-weight: 550; }
        .activity-logs-avatar { display: inline-flex; width: 1.7rem; height: 1.7rem; flex: 0 0 1.7rem; align-items: center; justify-content: center; border-radius: 999px; background: #e0e7ff; color: #4338ca; font-size: 0.68rem; font-weight: 700; }
        .activity-logs-avatar-image { object-fit: cover; }
        .activity-logs-date { display: block; color: #475569; white-space: nowrap; }
        .activity-logs-table td small { color: #94a3b8; font-size: 0.68rem; }
        .activity-logs-actions-column { text-align: right; }
        .activity-logs-details-button { border: 0; background: transparent; padding: 0; color: #4f46e5; font-size: 0.78rem; font-weight: 650; white-space: nowrap; cursor: pointer; }
        .activity-logs-details-button:hover { color: #3730a3; text-decoration: underline; }
        .activity-logs-empty { padding: 3rem 1rem !important; color: #94a3b8; text-align: center; }
        .activity-logs-pagination { padding: 0.9rem 1.25rem; }
        .activity-logs-modal-backdrop { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; background: rgb(15 23 42 / 40%); padding: 1rem; }
        .activity-logs-modal { width: min(100%, 34rem); border: 1px solid #e2e8f0; border-radius: 0.85rem; background: #fff; box-shadow: 0 20px 45px rgb(15 23 42 / 20%); }
        .activity-logs-modal-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; border-bottom: 1px solid #e2e8f0; padding: 1.1rem 1.25rem; }
        .activity-logs-modal-eyebrow { margin: 0 0 0.25rem; color: #6366f1; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .activity-logs-modal-heading h2 { margin: 0; color: #111827; font-size: 1.1rem; font-weight: 700; }
        .activity-logs-modal-close { display: inline-flex; border: 0; background: transparent; color: #64748b; cursor: pointer; }
        .activity-logs-actor-card { display: flex; align-items: center; gap: 0.7rem; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; }
        .activity-logs-actor-photo { display: inline-flex; width: 2.5rem; height: 2.5rem; flex: 0 0 2.5rem; align-items: center; justify-content: center; overflow: hidden; border-radius: 999px; background: #e0e7ff; color: #4338ca; font-size: 0.9rem; font-weight: 700; object-fit: cover; }
        .activity-logs-actor-card div { display: grid; gap: 0.15rem; min-width: 0; }
        .activity-logs-actor-card strong { color: #1f2937; font-size: 0.85rem; }
        .activity-logs-actor-card span:not(.activity-logs-avatar) { overflow-wrap: anywhere; color: #64748b; font-size: 0.72rem; }
        .activity-logs-details-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; padding: 1.25rem; }
        .activity-logs-details-grid div { min-width: 0; }
        .activity-logs-details-grid dt { color: #64748b; font-size: 0.68rem; font-weight: 650; text-transform: uppercase; }
        .activity-logs-details-grid dd { margin: 0.2rem 0 0; overflow-wrap: anywhere; color: #1f2937; font-size: 0.82rem; font-weight: 600; }
        .activity-logs-modal-notice { display: flex; gap: 0.6rem; margin: 0 1.25rem; border: 1px solid #c7d2fe; border-radius: 0.55rem; background: #eef2ff; padding: 0.75rem; color: #3730a3; }
        .activity-logs-modal-notice p { margin: 0; font-size: 0.75rem; line-height: 1.45; }
        .activity-logs-modal-footer { display: flex; justify-content: flex-end; border-top: 1px solid #e2e8f0; margin-top: 1.25rem; padding: 0.85rem 1.25rem; }
        .dark .activity-logs-filter-card, .dark .activity-logs-table-card, .dark .activity-logs-modal { border-color: #374151; background: #18181b; }
        .dark .activity-logs-heading h1, .dark .activity-logs-table-heading h2, .dark .activity-logs-filter-title, .dark .activity-logs-modal-heading h2, .dark .activity-logs-details-grid dd { color: #f9fafb; }
        .dark .activity-logs-field, .dark .activity-logs-heading p, .dark .activity-logs-table-heading p, .dark .activity-logs-privacy-note, .dark .activity-logs-details-grid dt { color: #a1a1aa; }
        .dark .activity-logs-field select, .dark .activity-logs-field input, .dark .activity-logs-date-row input { border-color: #52525b; background: #27272a; color: #f4f4f5; }
        .dark .activity-logs-date-row { border-color: #3f3f46; }
        .dark .activity-logs-date-label, .dark .activity-logs-date-to { color: #a1a1aa; }
        .dark .activity-logs-table-wrap, .dark .activity-logs-modal-heading, .dark .activity-logs-actor-card, .dark .activity-logs-modal-footer { border-color: #3f3f46; }
        .dark .activity-logs-table th { background: #27272a; color: #d4d4d8; }
        .dark .activity-logs-table th, .dark .activity-logs-table td { border-color: #3f3f46; }
        .dark .activity-logs-table tbody tr:hover { background: #27272a; }
        .dark .activity-logs-user-cell, .dark .activity-logs-actor-card strong { color: #e5e7eb; }
        .dark .activity-logs-action-create { background: #14532d; color: #bbf7d0; }
        .dark .activity-logs-action-update { background: #1e3a8a; color: #bfdbfe; }
        .dark .activity-logs-action-delete { background: #7f1d1d; color: #fecaca; }
        .dark .activity-logs-action-perform { background: #312e81; color: #e0e7ff; }
        .dark .activity-logs-actor-card span:not(.activity-logs-avatar) { color: #a1a1aa; }
        .dark .activity-logs-modal-notice { border-color: #4338ca; background: #312e81; color: #e0e7ff; }
        @media (max-width: 1100px) { .activity-logs-filter-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } .activity-logs-search-field { grid-column: span 2; } .activity-logs-date-row { align-items: end; } }
        @media (max-width: 700px) { .activity-logs-heading { align-items: start; } .activity-logs-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .activity-logs-search-field { grid-column: span 2; } .activity-logs-date-label { width: 100%; } .activity-logs-date-row input { flex: 1; min-width: 8rem; } .activity-logs-details-grid { grid-template-columns: 1fr; } }
    </style>
</x-filament-panels::page>
