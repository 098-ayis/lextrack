<button
    type="button"
    class="sidebar-collapse-inline"
    aria-controls="fi-main-sidebar"
    x-data="{}"
    x-cloak
    x-show="$store.sidebar.isOpen"
    x-bind:aria-expanded="$store.sidebar.isOpen"
    aria-label="Collapse sidebar"
    x-on:click="$store.sidebar.close()"
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
        <path stroke-linecap="round" stroke-linejoin="round" d="m16 9-3 3 3 3" />
    </svg>
</button>
