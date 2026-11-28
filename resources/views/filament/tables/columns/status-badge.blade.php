@php($status = $getState())

<span
    class="document-type-badge inline-flex items-center justify-center rounded-md px-2 py-0.5 text-center text-[11px] font-semibold leading-4"
    style="--badge-color: {{ $status['color'] ?? '#64748B' }};"
>
    {{ $status['label'] ?? '—' }}
</span>
