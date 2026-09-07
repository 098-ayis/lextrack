<x-filament-panels::page>
    <x-filament::section heading="Monthly accomplishment report">
        <p class="mb-4 text-sm text-gray-500">Generate a summary of documents received, processed, and completed, with a dated list of processing activities.</p>
        <form method="GET" action="{{ route('admin.reports.monthly') }}" target="_blank" class="flex flex-wrap items-end gap-4">
            <label class="grid gap-2 text-sm">Reporting month
                <input type="month" name="month" value="{{ now()->format('Y-m') }}" required class="rounded-lg border-gray-300 dark:bg-gray-900">
            </label>
            <x-filament::button type="submit" icon="heroicon-o-document-chart-bar">Generate report</x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
