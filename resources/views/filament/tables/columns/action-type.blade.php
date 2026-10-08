@php
    $actionType = $record->action_type;
    $color = $actionType
        ? \App\Models\ActionType::query()->where('action_name', $actionType)->value('color')
        : null;
@endphp

@if ($actionType)
    <span
        class="action-type-badge inline-flex items-center justify-center gap-1 rounded-md px-2 py-0.5 text-center text-[11px] font-semibold leading-4"
        style="--badge-color: {{ $color ?? '#64748B' }};"
    >
        {{ $actionType }}
    </span>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        No action assigned
    </span>
@endif
