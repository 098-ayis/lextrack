@php
    $viewer = auth()->user();
    $roleLabel = match (true) {
        $viewer?->hasRole(\App\Support\RoleSecurity::SUPER_ADMIN) => \App\Support\RoleSecurity::SUPER_ADMIN,
        $viewer?->hasRole(\App\Support\RoleSecurity::LEGAL_STAFF) => \App\Support\RoleSecurity::LEGAL_STAFF,
        $viewer?->hasRole('Client') => 'Client',
        default => null,
    };
@endphp

@if (filled($roleLabel))
    <div class="flex items-center">
        <span
            class="inline-flex items-center whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-semibold"
            style="border: 1px solid #d3dbf4 !important; background-color: #e5f0fe !important; color: #4b69bb !important;"
        >
            {{ $roleLabel }}
        </span>
    </div>
@endif
