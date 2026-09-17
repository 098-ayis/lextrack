@php
    $pageTitle = null;
    $route = request()->route();
    $pageClass = $route?->getAction('uses');

    if (
        is_string($pageClass)
        && class_exists($pageClass)
        && is_a($pageClass, \Filament\Pages\BasePage::class, true)
    ) {
        try {
            $pageTitle = app($pageClass)->getTitle();
        } catch (\Throwable) {
            // Record-based resource titles require the mounted page instance.
        }
    }

    if (
        blank($pageTitle)
        && is_string($pageClass)
        && class_exists($pageClass)
        && is_a($pageClass, \Filament\Resources\Pages\Page::class, true)
    ) {
        try {
            $resourceClass = $pageClass::getResource();
            $resourceLabel = $resourceClass::getTitleCaseModelLabel();

            $pageTitle = match (true) {
                is_a($pageClass, \Filament\Resources\Pages\CreateRecord::class, true) => "Create {$resourceLabel}",
                is_a($pageClass, \Filament\Resources\Pages\EditRecord::class, true) => "Edit {$resourceLabel}",
                is_a($pageClass, \Filament\Resources\Pages\ViewRecord::class, true) => "View {$resourceLabel}",
                default => null,
            };
        } catch (\Throwable) {
            // Fall back to the active navigation item below.
        }
    }

    if (blank($pageTitle) && filament()->hasNavigation()) {
        $findActiveItem = function ($items) use (&$findActiveItem) {
            foreach (collect($items) as $item) {
                if ($item->isActive()) {
                    return $item->getLabel();
                }

                if ($label = $findActiveItem($item->getChildItems())) {
                    return $label;
                }
            }

            return null;
        };

        foreach (filament()->getNavigation() as $group) {
            if ($pageTitle = $findActiveItem($group->getItems())) {
                break;
            }
        }
    }

    $pageTitle = filled($pageTitle) ? trim(strip_tags((string) $pageTitle)) : null;
@endphp

@if (filled($pageTitle))
    <div class="fi-admin-page-heading">
        <h1 class="fi-admin-page-title">
            {{ $pageTitle }}
        </h1>

        <p class="fi-admin-page-date">
            {{ now()->format('l, F j, Y') }}
        </p>
    </div>
@endif
