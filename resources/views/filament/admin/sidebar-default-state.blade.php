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
    class="sidebar-collapse-floating"
    aria-controls="fi-main-sidebar"
    x-data="{}"
    x-cloak
    x-bind:aria-expanded="$store.sidebar.isOpen"
    x-bind:aria-label="$store.sidebar.isOpen ? 'Collapse sidebar' : 'Expand sidebar'"
    x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
>
    <svg
        x-show="$store.sidebar.isOpen"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        aria-hidden="true"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7" />
    </svg>

    <svg
        x-show="! $store.sidebar.isOpen"
        x-cloak
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        aria-hidden="true"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
    </svg>
</button>
