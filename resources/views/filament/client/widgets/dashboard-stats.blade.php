<x-filament-widgets::widget>

    <div class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Documents -->
        <div
            class="rounded-lg border-t-[6px] border-t-orange-600 bg-white
                   p-4 shadow-sm sm:p-5 lg:p-6"
        >
            <h3
                class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500"
            >
                Total Documents
            </h3>

            <p
                class="text-3xl font-bold leading-none text-gray-900
                       sm:text-4xl"
            >
                {{ $total ?? 0 }}
            </p>
        </div>

        <!-- Pending -->
        <div
            class="rounded-lg border-t-[6px] border-t-yellow-500 bg-white
                   p-4 shadow-sm sm:p-5 lg:p-6"
        >
            <h3
                class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500"
            >
                Pending
            </h3>

            <p
                class="text-3xl font-bold leading-none text-gray-900
                       sm:text-4xl"
            >
                {{ $pending ?? 0 }}
            </p>
        </div>

        <!-- Active -->
        <div
            class="rounded-lg border-t-[6px] border-t-indigo-500 bg-white
                   p-4 shadow-sm sm:p-5 lg:p-6"
        >
            <h3
                class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500"
            >
                Active
            </h3>

            <p
                class="text-3xl font-bold leading-none text-gray-900
                       sm:text-4xl"
            >
                {{ $active ?? 0 }}
            </p>
        </div>

        <!-- Completed -->
        <div
            class="rounded-lg border-t-[6px] border-t-green-500 bg-white
                   p-4 shadow-sm sm:p-5 lg:p-6"
        >
            <h3
                class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500"
            >
                Completed
            </h3>

            <p
                class="text-3xl font-bold leading-none text-gray-900
                       sm:text-4xl"
            >
                {{ $completed ?? 0 }}
            </p>
        </div>

    </div>

</x-filament-widgets::widget>