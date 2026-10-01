<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-800 dark:bg-warning-950/40 dark:text-warning-200">
            Former users cannot authenticate or appear in normal user management. Their names and activity remain available for historical attribution.
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
