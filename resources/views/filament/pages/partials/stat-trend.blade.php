<div class="dashboard-stat-footer">
    <span class="dashboard-stat-info" title="Current month-to-date compared with the previous calendar month" aria-label="Current month-to-date compared with the previous calendar month">i</span>
    <span class="dashboard-stat-trend" title="Current month-to-date compared with the previous calendar month">
        <svg
            class="{{ $trend['direction'] > 0 ? 'text-emerald-600' : ($trend['direction'] < 0 ? 'rotate-180 text-rose-600' : 'text-violet-500') }}"
            viewBox="0 0 10 8"
            aria-hidden="true"
        >
            @if ($trend['direction'] === 0)
                <path d="M1 4h8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            @else
                <path d="M5 1 9 7H1L5 1Z" fill="currentColor" />
            @endif
        </svg>
        <span class="font-semibold {{ $trend['direction'] > 0 ? 'text-emerald-700 dark:text-emerald-400' : ($trend['direction'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-violet-700 dark:text-violet-300') }}">
            {{ $trend['value'] }}
        </span>
        <span class="dashboard-stat-period">vs last month</span>
    </span>
</div>
