<script>
    (() => {
        try {
            const defaultStateKey = 'adminSidebarDefaultClosed'

            if (localStorage.getItem(defaultStateKey) === null) {
                localStorage.setItem('isOpen', JSON.stringify(false))
                localStorage.setItem('isOpenDesktop', JSON.stringify(false))
                localStorage.setItem(defaultStateKey, JSON.stringify(true))
            }
        } catch (error) {
            // Filament handles unavailable browser storage gracefully.
        }
    })()
</script>

<div
    class="sidebar-collapsed-brand"
    x-data="{}"
    x-cloak
    x-show="! $store.sidebar.isOpen"
>
    <img
        src="{{ asset('images/lextrack-logo.png.png') }}"
        alt="LexTrack Bicol University Legal Office"
    >
</div>

<button
    type="button"
    class="sidebar-expand-below-logo"
    aria-controls="fi-main-sidebar"
    x-data="{}"
    x-cloak
    x-show="! $store.sidebar.isOpen"
    x-bind:aria-expanded="$store.sidebar.isOpen"
    aria-label="Expand sidebar"
    x-on:click="$store.sidebar.open()"
>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        aria-hidden="true"
    >
        <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
        <path stroke-linecap="round" d="M9 4.5v15" />
        <path stroke-linecap="round" stroke-linejoin="round" d="m13 9 3 3-3 3" />
    </svg>
</button>
