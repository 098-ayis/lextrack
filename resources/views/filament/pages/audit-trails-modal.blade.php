<div class="max-h-[60vh] overflow-y-auto">
    @forelse ($logs as $log)
        <div class="border-b border-gray-100 px-1 py-3 last:border-b-0">
            <div class="flex items-start gap-3">
                @if ($log->user && $log->user->profile_photo_url)
                    <img
                        src="{{ $log->user->profile_photo_url }}"
                        alt="{{ $log->user->name ?? 'User' }}"
                        class="h-8 w-8 shrink-0 rounded-full object-cover"
                    >
                @else
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-gray-200 text-xs font-bold text-gray-600"
                    >
                        {{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $log->action_type ?? 'Updated' }}
                        </p>

                        <time class="text-xs text-gray-500">
                            {{ $log->created_at?->format('g:i A - M d, Y') }}
                        </time>
                    </div>

                    <p class="mt-1 text-xs text-gray-600">
                        {{ $log->action_details ?? 'No details recorded.' }}
                    </p>

                    <p class="mt-1 text-xs font-medium text-gray-500">
                        {{ $log->user->name ?? 'User' }}
                    </p>
                </div>
            </div>
        </div>
    @empty
        <p class="px-1 py-8 text-center text-sm text-gray-400">
            No activity recorded.
        </p>
    @endforelse
</div>
