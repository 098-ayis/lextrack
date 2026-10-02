<x-filament-panels::page>
    <form wire:submit="save" class="system-settings-form">
        {{ $this->form }}

        <div class="system-settings-actions">
            <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled" wire:target="save">
                Save changes
            </x-filament::button>
        </div>
    </form>

    <style>
        .system-settings-form { display: grid; gap: 1rem; }
        .system-settings-actions { display: flex; justify-content: flex-end; }
        .system-settings-link-list { display: grid; gap: .65rem; }
        .system-settings-link-list a { display: flex; flex-direction: column; gap: .15rem; padding: .8rem .9rem; border: 1px solid rgb(226 232 240); border-radius: .65rem; text-decoration: none; transition: background .15s, border-color .15s; }
        .system-settings-link-list a:hover { background: rgb(248 250 252); border-color: rgb(129 140 248); }
        .system-settings-link-list span { color: rgb(67 56 202); font-size: .85rem; font-weight: 700; }
        .system-settings-link-list small { color: rgb(100 116 139); font-size: .75rem; }
        .dark .system-settings-link-list a { border-color: rgb(55 65 81); }
        .dark .system-settings-link-list a:hover { background: rgb(31 41 55); border-color: rgb(129 140 248); }
        .dark .system-settings-link-list small { color: rgb(156 163 175); }
    </style>
</x-filament-panels::page>
