<x-filament-widgets::widget>

    <div class="client-dashboard-documents space-y-4">

        {{-- SEARCH / FILTERS / ACTIONS --}}
        <div class="flex w-full flex-col gap-4">

            {{-- LEFT SIDE --}}
            <div class="order-2 flex w-full min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-center">

                {{-- SEARCH --}}
                <div class="relative w-full sm:max-w-md sm:flex-none">

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
            <div class="order-1 flex w-full items-center justify-end gap-3">

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


        {{-- RECENT DOCUMENTS TITLE --}}
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                Recent Documents
            </h2>

            <a
                href="{{ \App\Filament\Client\Pages\Documents::getUrl() }}"
                class="text-sm font-semibold text-[#6366F1] transition hover:underline dark:text-indigo-400"
            >
                View all
            </a>
        </div>

        {{-- DOCUMENT CARDS --}}
        @php
            $documents = $this->getDocuments();
        @endphp

        @if ($documents->isNotEmpty())
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($documents as $document)
                    @php
                        $isRequested = (int) $document->user_id !== (int) auth()->id();
                        $cardStatus = $isRequested
                            ? ($document->documentRequests->first()?->status ?? $document->status)
                            : $document->status;

                        $statusLabel = match ($cardStatus) {
                            'in_progress' => 'In Progress',
                            'outgoing' => 'In Progress',
                            'completed' => 'Completed',
                            'archived' => 'Completed',
                            'accepted' => 'Accepted',
                            default => ucwords(str_replace('_', ' ', (string) $cardStatus)),
                        };

                        $statusClasses = match ($cardStatus) {
                            'pending' => 'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',
                            'in_progress', 'outgoing' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300',
                            'completed', 'archived' => 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300',
                            'accepted' => 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300',
                            'rejected' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
                            default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300',
                        };

                        $previewUrl = $document->latestVersion?->file_path
                            ? route('client.document.preview', ['document' => $document->document_id])
                            : null;

                        $extension = strtolower(pathinfo(
                            (string) $document->latestVersion?->file_path,
                            PATHINFO_EXTENSION
                        ));
                    @endphp

                    <a
                        href="{{ \App\Filament\Client\Pages\ViewDocument::getUrl([
                            'document' => $document->document_id,
                            'from' => 'dashboard',
                        ]) }}"
                        class="group relative flex aspect-square min-h-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-[#6366F1] hover:shadow-lg dark:border-gray-700 dark:bg-[#17181c] dark:hover:border-indigo-400"
                    >
                        <span class="absolute right-3 top-3 z-10 rounded-md border px-2 py-0.5 text-[11px] font-semibold shadow-sm {{ $isRequested
                            ? 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-800 dark:bg-purple-950 dark:text-purple-300'
                            : 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                            {{ $isRequested ? 'Requested' : 'Uploaded' }}
                        </span>

                        {{-- PREVIEW --}}
                        <div class="relative aspect-[4/3] overflow-hidden border-b border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-900">
                            @if ($previewUrl && in_array($extension, ['jpg', 'jpeg', 'png', 'webp']))
                                <img
                                    src="{{ $previewUrl }}"
                                    alt="{{ $document->particulars ?: 'Document preview' }}"
                                    class="h-full w-full object-cover object-top transition duration-300 group-hover:scale-105"
                                >
                            @elseif ($previewUrl && $extension === 'pdf')
                                <iframe
                                    src="{{ $previewUrl }}"
                                    title="{{ $document->particulars ?: 'Document preview' }}"
                                    class="pointer-events-none h-full w-full border-0"
                                ></iframe>
                            @else
                                <div class="flex h-full items-center justify-center text-gray-400 dark:text-gray-500">
                                    <svg
                                        class="h-16 w-16"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.25"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V9.75M10.5 2.25V7.125c0 .621.504 1.125 1.125 1.125h4.875"
                                        />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- DETAILS --}}
                        <div class="flex flex-1 flex-col p-3">
                            <h3 class="line-clamp-2 text-sm font-bold text-gray-900 dark:text-gray-100">
                                {{ $document->particulars ?: 'Untitled document' }}
                            </h3>

                            <p class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $document->lao_number ?: 'LAO number not assigned' }}
                            </p>

                            <div class="mt-auto flex items-center justify-between gap-3 pt-5">
                                <span class="inline-flex items-center rounded-lg border px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>

                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $document->created_at?->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-gray-200 bg-white px-6 py-12 text-center shadow-sm dark:border-gray-700 dark:bg-[#17181c]">
                <svg
                    class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V9.75M10.5 2.25V7.125c0 .621.504 1.125 1.125 1.125h4.875"
                    />
                </svg>

                <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                    No documents found
                </p>
            </div>
        @endif

    </div>

</x-filament-widgets::widget>
