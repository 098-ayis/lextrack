<button
    type="button"
    class="sidebar-expand-empty-space"
    aria-controls="fi-main-sidebar"
    x-data="{}"
    x-cloak
    x-show="! $store.sidebar.isOpen"
    x-bind:aria-expanded="$store.sidebar.isOpen"
    aria-label="Expand sidebar"
    x-on:click="$store.sidebar.open()"
></button>
