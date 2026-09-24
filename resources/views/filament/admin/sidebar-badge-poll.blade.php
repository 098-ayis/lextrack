<div class="hidden" aria-hidden="true"></div>

<script>
    (() => {
        if (window.__lexTrackNavigationBadgePoll) {
            return;
        }

        window.__lexTrackNavigationBadgePoll = true;

        const endpoint = @js(route('admin.navigation.counts'));
        const targets = [
            {
                path: new URL(@js(route('filament.admin.pages.incoming')), window.location.origin).pathname.replace(/\/$/, ''),
                countKey: 'documents',
            },
            {
                path: new URL(@js(route('filament.admin.pages.document-requests')), window.location.origin).pathname.replace(/\/$/, ''),
                countKey: 'requests',
            },
        ];

        const updateBadge = (path, count) => {
            const link = [...document.querySelectorAll('.fi-sidebar-item-btn')]
                .find((candidate) => {
                    try {
                        return new URL(candidate.href, window.location.href).pathname.replace(/\/$/, '') === path;
                    } catch {
                        return false;
                    }
                });

            if (!link) {
                return;
            }

            let container = link.querySelector('.fi-sidebar-item-badge-ctn');
            const numericCount = Number(count) || 0;

            if (numericCount > 0 && !container) {
                container = document.createElement('span');
                container.className = 'fi-sidebar-item-badge-ctn';

                const badge = document.createElement('span');
                badge.className = 'fi-badge fi-size-sm fi-color-danger';
                container.appendChild(badge);
                link.appendChild(container);
            }

            if (!container) {
                return;
            }

            container.hidden = numericCount === 0;

            const badge = container.querySelector('.fi-badge');

            if (badge) {
                badge.textContent = String(numericCount);
            }
        };

        const updateNotificationBadge = (count) => {
            const button = document.querySelector('.fi-topbar-database-notifications-btn');

            if (!button) {
                return;
            }

            let container = button.querySelector('.fi-icon-btn-badge-ctn');
            const numericCount = Number(count) || 0;

            if (numericCount > 0 && !container) {
                container = document.createElement('div');
                container.className = 'fi-icon-btn-badge-ctn';

                const badge = document.createElement('span');
                badge.className = 'fi-badge fi-size-xs fi-color-danger';
                container.appendChild(badge);
                button.appendChild(container);
            }

            if (!container) {
                return;
            }

            container.hidden = numericCount === 0;

            const badge = container.querySelector('.fi-badge');

            if (badge) {
                badge.textContent = String(numericCount);
            }
        };

        const refreshDatabaseNotifications = () => {
            const button = document.querySelector('.fi-topbar-database-notifications-btn');
            const componentRoot = button?.closest('[wire\\:id]');
            const componentId = componentRoot?.getAttribute('wire:id');

            if (!componentId || !window.Livewire?.find) {
                return;
            }

            window.Livewire.find(componentId)?.$refresh();
        };

        const refreshNavigationBadges = async () => {
            try {
                const url = new URL(endpoint, window.location.href);
                url.searchParams.set('_', Date.now().toString());

                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    return;
                }

                const counts = await response.json();
                targets.forEach(({ path, countKey }) => {
                    updateBadge(path, counts[countKey]);
                });
                updateNotificationBadge(counts.notifications);
            } catch {
                // A temporary polling failure should not affect navigation.
            }
        };

        refreshNavigationBadges();
        window.setInterval(refreshNavigationBadges, 2000);
        window.addEventListener('livewire:navigated', refreshNavigationBadges);
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            if (!event.target.closest('.fi-topbar-database-notifications-btn')) {
                return;
            }

            window.setTimeout(refreshDatabaseNotifications, 50);
        });
    })();
</script>
