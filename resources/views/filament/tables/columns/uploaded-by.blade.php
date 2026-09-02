@if ($record->user)
    <div class="flex items-center gap-3 text-left">
        @if ($record->user->profile_photo_url)
            <img
                src="{{ $record->user->profile_photo_url }}"
                alt="{{ $record->user->name }}"
                class="h-9 w-9 shrink-0 rounded-full border border-gray-300 object-cover dark:border-gray-600"
            >
        @else
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-gray-200 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-200">
                    {{ strtoupper(substr($record->user->name ?? 'U', 0, 1)) }}
                </span>
            </div>
        @endif

        <div class="flex min-w-0 flex-col">
            <span class="truncate text-xs font-semibold text-gray-900 dark:text-gray-100">
                {{ $record->user->name }}
            </span>
            <span class="truncate text-xs text-gray-500 dark:text-gray-400">
                {{ $record->user->email }}
            </span>
        </div>
    </div>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        Unknown
    </span>
@endif
