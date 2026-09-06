@php
    $actionType = $record->action_type;
    $color = $actionType
        ? \App\Models\ActionType::query()->where('action_name', $actionType)->value('color')
        : null;
@endphp

@if ($actionType)
    <span
        class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold text-white"
        style="background-color: {{ $color ?? '#64748B' }};"
    >
        {{ $actionType }}
    </span>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        No action assigned
    </span>
@endif
