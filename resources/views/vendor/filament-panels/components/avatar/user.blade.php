@props([
    'user' => filament()->auth()->user(),
])

@php
    $src = $user->getProfilePhotoUrl();
    $alt = __('filament-panels::layout.avatar.alt', ['name' => filament()->getUserName($user)]);
@endphp

<x-filament::avatar
    :src="$src"
    :alt="$alt"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->merge(['referrerpolicy' => 'no-referrer'])
            ->class(['fi-user-avatar'])
    "
/>
