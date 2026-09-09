@if ($record->user)
    @php($userName = $record->user->name ?: 'Unknown')

    <div
        class="flex items-center justify-center"
        title="{{ $userName }}"
        aria-label="Requested by {{ $userName }}"
    >
        @if ($record->user->profile_photo_url)
            <img
                src="{{ $record->user->profile_photo_url }}"
                alt="{{ $userName }}"
                class="h-8 w-8 rounded-full border border-gray-300 object-cover dark:border-gray-600"
            >
        @else
            <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-gray-200 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-200">
                    {{ strtoupper(substr($userName, 0, 1)) }}
                </span>
            </div>
        @endif
    </div>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400" title="Unknown requester">
        —
    </span>
@endif
