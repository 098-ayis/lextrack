<form
    method="POST"
    action="{{ filament()->getLogoutUrl() }}"
    class="sidebar-logout-form"
>
    @csrf

    <button type="submit" class="sidebar-logout-button">
        <svg
            class="sidebar-logout-icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 15l3-3m0 0l-3-3m3 3H9" />
        </svg>

        <span class="sidebar-logout-label">Logout</span>
    </button>
</form>
