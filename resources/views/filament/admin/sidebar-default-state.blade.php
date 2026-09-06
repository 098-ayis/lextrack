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
