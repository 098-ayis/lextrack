<div class="space-y-4">
    @forelse ($logs as $log)
        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="flex items-start justify-between gap-3">
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ ucwords(str_replace('_', ' ', (string) $log->action_type)) }}
                </p>
                <time class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $log->created_at?->format('M d, Y • g:i A') }}
                </time>
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                By: {{ $log->user?->name ?? 'System' }}
            </p>
            @if ($log->action_type === 'pickup_rescheduled')
                <dl class="mt-2 grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    <div><dt class="inline font-medium">Previous:</dt> <dd class="inline">{{ $log->old_value ? \Carbon\Carbon::parse($log->old_value)->format('M d, Y g:i A') : '—' }}</dd></div>
                    <div><dt class="inline font-medium">New:</dt> <dd class="inline">{{ $log->new_value ? \Carbon\Carbon::parse($log->new_value)->format('M d, Y g:i A') : '—' }}</dd></div>
                </dl>
            @elseif ($log->old_value || $log->new_value)
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                    {{ $log->old_value ?? '—' }} → {{ $log->new_value ?? '—' }}
                </p>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No history has been recorded for this request.</p>
    @endforelse
</div>
