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

    if (!window.__lextrackSidebarClickHandlerInstalled) {
        window.__lextrackSidebarClickHandlerInstalled = true

        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target : null
            const sidebar = target?.closest('#fi-main-sidebar')

            if (!sidebar || target.closest('a, button, input, select, textarea, [role="button"]')) {
                return
            }

            const sidebarStore = window.Alpine?.store?.('sidebar')

            if (!sidebarStore) {
                return
            }

            if (sidebarStore.isOpen) {
                sidebarStore.close()
            } else {
                sidebarStore.open()
            }
        })
    }
</script>

<div
    class="sidebar-collapsed-brand-toggle"
    x-data="{}"
    x-cloak
    x-show="! $store.sidebar.isOpen"
>
    <span class="sidebar-collapsed-brand" aria-hidden="true">
        <img
            src="{{ asset('images/lextrack-logo.png.png') }}"
            alt=""
        >
    </span>

    <button
        type="button"
        class="sidebar-expand-on-logo"
        aria-controls="fi-main-sidebar"
        x-bind:aria-expanded="$store.sidebar.isOpen"
        aria-label="Expand sidebar"
        x-on:click.stop.prevent="$store.sidebar.open()"
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
</div>
