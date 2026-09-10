<x-filament-widgets::widget>

    <div class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Documents -->
        <a
            href="{{ \App\Filament\Client\Pages\Documents::getUrl(['tab' => 'all']) }}"
            class="block rounded-lg transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div
                class="rounded-lg border-t-[6px] border-t-orange-600 bg-white
                       p-4 shadow-sm dark:bg-gray-800 dark:ring-1 dark:ring-white/10
                       sm:p-5 lg:p-6"
            >
            <h3
                class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500
                       dark:text-gray-400"
            >
                Total Documents
            </h3>

            <p
                class="text-3xl font-bold leading-none text-gray-900
                       dark:text-gray-100 sm:text-4xl"
            >
                {{ $total ?? 0 }}
            </p>
            </div>
        </a>

        <!-- Pending -->
        <a
            href="{{ \App\Filament\Client\Pages\Documents::getUrl(['tab' => 'pending']) }}"
            class="block rounded-lg transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div
                class="rounded-lg border-t-[6px] border-t-yellow-500 bg-white
                       p-4 shadow-sm dark:bg-gray-800 dark:ring-1 dark:ring-white/10
                       sm:p-5 lg:p-6"
            >
            <h3
                class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500
                       dark:text-gray-400"
            >
                Pending
            </h3>

            <p
                class="text-3xl font-bold leading-none text-gray-900
                       dark:text-gray-100 sm:text-4xl"
            >
                {{ $pending ?? 0 }}
            </p>
            </div>
        </a>

        <!-- Active -->
        <a
            href="{{ \App\Filament\Client\Pages\Documents::getUrl(['tab' => 'in_progress']) }}"
            class="block rounded-lg transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div
                class="rounded-lg border-t-[6px] border-t-indigo-500 bg-white
                       p-4 shadow-sm dark:bg-gray-800 dark:ring-1 dark:ring-white/10
                       sm:p-5 lg:p-6"
            >
            <h3
                class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500
                       dark:text-gray-400"
            >
                Active
            </h3>

            <p
                class="text-3xl font-bold leading-none text-gray-900
                       dark:text-gray-100 sm:text-4xl"
            >
                {{ $active ?? 0 }}
            </p>
            </div>
        </a>

        <!-- Completed -->
        <a
            href="{{ \App\Filament\Client\Pages\Documents::getUrl(['tab' => 'completed']) }}"
            class="block rounded-lg transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div
                class="rounded-lg border-t-[6px] border-t-green-500 bg-white
                       p-4 shadow-sm dark:bg-gray-800 dark:ring-1 dark:ring-white/10
                       sm:p-5 lg:p-6"
            >
            <h3
                class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500
                       dark:text-gray-400"
            >
                Completed
            </h3>

            <p
                class="text-3xl font-bold leading-none text-gray-900
                       dark:text-gray-100 sm:text-4xl"
            >
                {{ $completed ?? 0 }}
            </p>
            </div>
        </a>

    </div>

</x-filament-widgets::widget>
