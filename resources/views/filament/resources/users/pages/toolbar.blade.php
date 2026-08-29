@php
    $roleOptions = \App\Models\User::query()
        ->whereNotNull('role_name')
        ->where('role_name', '!=', '')
        ->distinct()
        ->orderBy('role_name')
        ->pluck('role_name', 'role_name');

    $statusOptions = \App\Models\User::STATUS_OPTIONS;

    $dateOptions = \App\Models\User::query()
        ->whereNotNull('join_date')
        ->where('join_date', '!=', '')
        ->distinct()
        ->orderByDesc('join_date')
        ->pluck('join_date', 'join_date');
@endphp

<div class="users-management-toolbar">
    <div class="users-toolbar-main">
        <label class="users-toolbar-search">
            <span class="fi-sr-only">Search users</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="6.5" />
                <path d="m16 16 4.5 4.5" />
            </svg>
            <input type="search" placeholder="Search" wire:model.live.debounce.500ms="tableSearch">
        </label>

        <label class="users-toolbar-select users-toolbar-role" x-data>
            <span class="fi-sr-only">Filter by role</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="3.5" />
                <path d="M5.5 20c.7-3.2 2.8-5 6.5-5s5.8 1.8 6.5 5" />
            </svg>
            <select x-ref="filter" wire:model.live="tableFilters.role_name.value">
                <option value="">Role</option>
                @foreach ($roleOptions as $value => $label)
                    <option value="{{ $value }}">{{ ucfirst($label) }}</option>
                @endforeach
            </select>
            <svg class="users-toolbar-chevron" viewBox="0 0 24 24" aria-hidden="true"
                x-on:click.stop.prevent="if ($refs.filter.showPicker) { $refs.filter.showPicker() } else { $refs.filter.click() }">
                <path d="m7 9 5 5 5-5" />
            </svg>
        </label>

        <label class="users-toolbar-select users-toolbar-status" x-data>
            <span class="fi-sr-only">Filter by status</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="7" />
                <path d="m9.5 12 1.7 1.7 3.6-4" />
            </svg>
            <select x-ref="filter" wire:model.live="tableFilters.status.value">
                <option value="">Status</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ ucfirst($label) }}</option>
                @endforeach
            </select>
            <svg class="users-toolbar-chevron" viewBox="0 0 24 24" aria-hidden="true"
                x-on:click.stop.prevent="if ($refs.filter.showPicker) { $refs.filter.showPicker() } else { $refs.filter.click() }">
                <path d="m7 9 5 5 5-5" />
            </svg>
        </label>

        <label class="users-toolbar-select users-toolbar-date" x-data>
            <span class="fi-sr-only">Filter by date</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="5" y="6.5" width="14" height="13" rx="1.5" />
                <path d="M8 4v5M16 4v5M5 10h14" />
            </svg>
            <select x-ref="filter" wire:model.live="tableFilters.join_date.value">
                <option value="">Date</option>
                @foreach ($dateOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <svg class="users-toolbar-chevron" viewBox="0 0 24 24" aria-hidden="true"
                x-on:click.stop.prevent="if ($refs.filter.showPicker) { $refs.filter.showPicker() } else { $refs.filter.click() }">
                <path d="m7 9 5 5 5-5" />
            </svg>
        </label>
    </div>

    <div class="users-toolbar-actions">
        <a href="{{ route('admin.users.export') }}" target="_blank" rel="noopener" class="users-toolbar-export">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 15V4m0 0L8.5 7.5M12 4l3.5 3.5" />
                <path d="M5 12v6.5A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5V12" />
            </svg>
            <span>Export</span>
        </a>

        <button type="button" wire:click="mountTableAction('create')" class="users-toolbar-add">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            <span>Add User</span>
        </button>
    </div>
</div>
