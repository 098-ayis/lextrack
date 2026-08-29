<x-filament-widgets::widget>

    <div class="client-dashboard-documents space-y-4">

        {{-- RECENT DOCUMENTS TITLE --}}
        <div class="flex items-center justify-between">

    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
        Recent Documents
    </h2>

    <a
        href="{{ \App\Filament\Client\Pages\Documents::getUrl() }}"
        class="text-sm font-semibold text-[#6366F1] dark:text-indigo-400
               transition hover:underline"
    >
        View all
    </a>

</div>


        {{-- SEARCH / FILTERS / ACTIONS --}}
        <div class="flex flex-col justify-between w-full gap-4 sm:flex-row sm:items-start">

            {{-- LEFT SIDE --}}
            <div class="w-full max-w-md space-y-3">

                {{-- SEARCH --}}
                <div class="relative w-full">

                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <svg
                            class="w-5 h-5 text-gray-400"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>

                    <input
                        type="text"
                        wire:model.live.debounce.400ms="documentSearch"
                        placeholder="Search for documents..."
                        class="block w-full py-2.5 pl-11 pr-4 text-sm
                               bg-white border border-gray-300 rounded-full
                               text-gray-900 placeholder:text-gray-400
                               dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100
                               dark:placeholder:text-gray-400
                               focus:outline-none focus:ring-2
                               focus:ring-[#6366F1]
                               focus:border-transparent"
                    >

                </div>


                {{-- FILTERS --}}
                <div class="flex flex-wrap items-center gap-2">

                    {{-- TYPE --}}
                    <div class="flex items-center gap-1.5">

                        <div class="relative w-44">

                            <select
                                wire:model.live="documentType"
                                class="appearance-none w-full rounded-lg border-2
                                       {{ $documentType
                                            ? 'bg-[#F0F1FF] border-[#6366F1] dark:bg-indigo-950 dark:border-indigo-400'
                                            : 'bg-white border-gray-300 dark:bg-gray-800 dark:border-gray-600'
                                       }}
                                       py-2 pl-3 pr-9
                                       text-xs font-semibold text-gray-900 dark:text-gray-100
                                       focus:outline-none focus:ring-0"
                            >
                                <option value="">Type</option>
                                <option value="1">MOA</option>
                                <option value="2">Correspondence</option>
                                <option value="3">Contract</option>
                                <option value="4">Proposal</option>
                                <option value="5">Procurement</option>
                                <option value="6">Reference Slip</option>
                                <option value="7">Clearance</option>
                                <option value="8">MOU</option>
                                <option value="9">NDA</option>
                                <option value="10">DOD</option>
                                <option value="11">GBA</option>
                                <option value="12">Others</option>
                            </select>

                            <svg
                                class="pointer-events-none absolute right-2.5 top-1/2
                                       h-3.5 w-3.5 -translate-y-1/2
                                       {{ $documentType
                                            ? 'text-[#6366F1] dark:text-indigo-400'
                                            : 'text-gray-500 dark:text-gray-400'
                                       }}"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02
                                       L10 11.168l3.71-3.938
                                       a.75.75 0 111.08 1.04l-4.25 4.5
                                       a.75.75 0 01-1.08 0l-4.25-4.5
                                       a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd"
                                />
                            </svg>

                        </div>

                        @if ($documentType)
                            <button
                                type="button"
                                wire:click="clearType"
                                class="flex h-[34px] w-[34px]
                                       items-center justify-center
                                       rounded-lg border-2 border-[#6366F1]
                                       bg-[#F0F1FF] text-[#6366F1]
                                       dark:bg-indigo-950 dark:text-indigo-400
                                       hover:bg-[#E4E5FF] dark:hover:bg-indigo-900"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18 18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        @endif

                    </div>


                    {{-- STATUS --}}
                    <div class="flex items-center gap-1.5">

                        <div class="relative w-36">

                            <select
                                wire:model.live="documentStatus"
                                class="appearance-none w-full rounded-lg border-2
                                       {{ $documentStatus
                                            ? 'bg-[#F0F1FF] border-[#6366F1] dark:bg-indigo-950 dark:border-indigo-400'
                                            : 'bg-white border-gray-300 dark:bg-gray-800 dark:border-gray-600'
                                       }}
                                       py-2 pl-3 pr-9
                                       text-xs font-semibold text-gray-900 dark:text-gray-100
                                       focus:outline-none focus:ring-0"
                            >
                                <option value="">Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>

                            <svg
                                class="pointer-events-none absolute right-2.5 top-1/2
                                       h-3.5 w-3.5 -translate-y-1/2
                                       {{ $documentStatus
                                            ? 'text-[#6366F1] dark:text-indigo-400'
                                            : 'text-gray-500 dark:text-gray-400'
                                       }}"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02
                                       L10 11.168l3.71-3.938
                                       a.75.75 0 111.08 1.04l-4.25 4.5
                                       a.75.75 0 01-1.08 0l-4.25-4.5
                                       a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd"
                                />
                            </svg>

                        </div>

                        @if ($documentStatus)
                            <button
                                type="button"
                                wire:click="clearStatus"
                                class="flex h-[34px] w-[34px]
                                       items-center justify-center
                                       rounded-lg border-2 border-[#6366F1]
                                       bg-[#F0F1FF] text-[#6366F1]
                                       dark:bg-indigo-950 dark:text-indigo-400
                                       hover:bg-[#E4E5FF] dark:hover:bg-indigo-900"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18 18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        @endif

                    </div>

                </div>

            </div>


            {{-- RIGHT SIDE --}}
            <div class="flex items-center w-full gap-3 sm:w-auto">

                {{-- REQUEST --}}
                <a
                    href="/client/request-document"
                    class="inline-flex items-center justify-center gap-2
                           w-36 px-5 py-2.5 text-sm font-semibold
                           text-[#6366F1] bg-white dark:bg-gray-800 dark:text-indigo-400
                           border border-[#6366F1]
                           dark:border-indigo-400
                           rounded-full"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7
                               a2 2 0 01-2-2V5
                               a2 2 0 012-2h5.586
                               a1 1 0 01.707.293
                               l3.414 3.414
                               A1 1 0 0117 7.414V19
                               a2 2 0 01-2 2z"
                        />
                    </svg>

                    Request
                </a>

                {{-- UPLOAD --}}
                <a
                    href="/client/upload"
                    class="inline-flex items-center justify-center gap-2
                           w-36 px-5 py-2.5 text-sm font-semibold
                           text-white bg-[#6366F1]
                           border border-[#6366F1]
                           rounded-full"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3
                               M6.75 19.5a4.5 4.5 0 01-1.41-8.775
                               5.25 5.25 0 0110.233-2.33
                               3 3 0 013.758 3.848
                               A3.752 3.752 0 0118 19.5H6.75z"
                        />
                    </svg>

                    Upload
                </a>

            </div>

        </div>


        {{-- TABLE --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white
                    dark:border-gray-700 dark:bg-gray-900">
            {{ $this->table }}
        </div>

    </div>

</x-filament-widgets::widget>
