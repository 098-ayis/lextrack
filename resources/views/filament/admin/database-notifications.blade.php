@php
    $notifications = $this->getNotifications();
    $unreadNotificationsCount = $this->getUnreadNotificationsCount();
    $hasAnyNotifications = $this->hasAnyNotifications();
    $isPaginated = $notifications instanceof \Illuminate\Contracts\Pagination\Paginator && $notifications->hasPages();
    $pollingInterval = $this->getPollingInterval();
@endphp

<div class="fi-no-database">
    <x-filament::modal
        :alignment="$hasAnyNotifications ? null : \Filament\Support\Enums\Alignment::Center"
        aria-labelledby="database-notifications.heading"
        close-button
        :description="$hasAnyNotifications ? null : __('filament-notifications::database.modal.empty.description')"
        :extra-modal-window-attribute-bag="
            new \Filament\Support\View\ComponentAttributeBag([
                'autofocus' => true,
                'tabindex' => '-1',
            ])
        "
        :heading="$hasAnyNotifications ? null : __('filament-notifications::database.modal.empty.heading')"
        :icon="$hasAnyNotifications ? null : \Filament\Support\Icons\Heroicon::OutlinedBellSlash"
        :icon-alias="
            $hasAnyNotifications
            ? null
            : \Filament\Notifications\View\NotificationsIconAlias::DATABASE_MODAL_EMPTY_STATE
        "
        :icon-color="$hasAnyNotifications ? null : 'gray'"
        id="database-notifications"
        slide-over
        :sticky-header="$hasAnyNotifications"
        teleport="body"
        width="md"
        class="fi-no-database"
        :attributes="
            new \Filament\Support\View\ComponentAttributeBag([
                'wire:poll.' . $pollingInterval => $pollingInterval ? '' : false,
            ])
        "
    >
        @if ($trigger = $this->getTrigger())
            <x-slot name="trigger">
                {{ $trigger->with(['unreadNotificationsCount' => $unreadNotificationsCount]) }}
            </x-slot>
        @endif

        @if ($hasAnyNotifications)
            <x-slot name="header">
                <div class="fi-notification-drawer-header">
                    <div class="fi-notification-drawer-heading-row">
                        <h2 id="database-notifications.heading" class="fi-modal-heading">
                            Notifications
                        </h2>
                    </div>

                    <div class="fi-notification-filter-row">
                        <div class="fi-notification-filters" role="tablist" aria-label="Notification filters">
                            <button
                                type="button"
                                wire:click="setNotificationFilter('all')"
                                class="fi-notification-filter {{ $notificationFilter === 'all' ? 'is-active' : '' }}"
                                role="tab"
                                aria-selected="{{ $notificationFilter === 'all' ? 'true' : 'false' }}"
                            >
                                All
                            </button>
                            <button
                                type="button"
                                wire:click="setNotificationFilter('unread')"
                                class="fi-notification-filter {{ $notificationFilter === 'unread' ? 'is-active' : '' }}"
                                role="tab"
                                aria-selected="{{ $notificationFilter === 'unread' ? 'true' : 'false' }}"
                            >
                                Unread
                            </button>
                        </div>

                        <div
                            x-data="{ open: false }"
                            x-on:click.outside="open = false"
                            class="fi-notification-menu"
                        >
                            <button
                                type="button"
                                x-on:click="open = ! open"
                                class="fi-icon-btn"
                                aria-label="Notification options"
                                title="Notification options"
                                :aria-expanded="open.toString()"
                            >
                                <x-heroicon-m-ellipsis-horizontal class="h-5 w-5" />
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-transition.origin.top.right
                                class="fi-notification-menu-popover"
                                role="menu"
                            >
                                @if ($unreadNotificationsCount)
                                    <button
                                        type="button"
                                        wire:click="markAllNotificationsAsRead"
                                        x-on:click="open = false"
                                        class="fi-notification-menu-item"
                                        role="menuitem"
                                    >
                                        Mark all as read
                                    </button>
                                @else
                                    <span class="fi-notification-menu-empty">All notifications are read</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>

            <div
                aria-label="Notifications"
                role="list"
                class="fi-no-notifications"
            >
                @forelse ($notifications as $notification)
                    <div
                        role="listitem"
                        wire:key="{{ $notification->getKey() }}.database-notifications.ctn"
                        @class([
                            'fi-no-notification-read-ctn' => ! $notification->unread(),
                            'fi-no-notification-unread-ctn' => $notification->unread(),
                        ])
                    >
                        @if ($notification->unread())
                            <span class="fi-sr-only">
                                {{ __('filament-notifications::database.modal.unread_label') }}
                            </span>
                        @endif

                        {{ $this->getNotification($notification)->inline() }}
                    </div>
                @empty
                    <div class="fi-notification-empty-filter">
                        {{ $notificationFilter === 'unread' ? 'You are all caught up.' : 'No notifications yet.' }}
                    </div>
                @endforelse
            </div>

            @if ($broadcastChannel = $this->getBroadcastChannel())
                @script
                    <script>
                        window.addEventListener('EchoLoaded', () => {
                            window.Echo.private(@js($broadcastChannel)).listen(
                                '.database-notifications.sent',
                                () => {
                                    setTimeout(
                                        () => $wire.call('$refresh'),
                                        500,
                                    )
                                },
                            )
                        })

                        if (window.Echo) {
                            window.dispatchEvent(new CustomEvent('EchoLoaded'))
                        }
                    </script>
                @endscript
            @endif

            @if ($isPaginated)
                <x-slot name="footer">
                    <x-filament::pagination :paginator="$notifications" />
                </x-slot>
            @endif
        @endif
    </x-filament::modal>
</div>
