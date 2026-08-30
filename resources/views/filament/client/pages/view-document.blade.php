<x-filament-panels::page>

    <div class="space-y-5">

        {{-- HEADER --}}
        <div class="flex items-center gap-3">

            <button
                type="button"
                onclick="window.history.back()"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300
                       bg-white p-2 text-gray-700 shadow-sm transition hover:bg-gray-50
                       dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
                title="Back"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                    />
                </svg>
            </button>

            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $documentRecord->particulars ?: 'Document Details' }}
                </h1>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $documentRecord->lao_number ?: 'LAO number not yet assigned' }}
                </p>
            </div>

        </div>

        {{-- MAIN CONTENT --}}
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

            {{-- LEFT: DOCUMENT PREVIEW --}}
            <div class="xl:col-span-2">

                <div
                    class="overflow-hidden rounded-xl border border-gray-200
                           bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >

                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Document Preview
                        </h2>
                    </div>

                    <div class="h-[70vh] min-h-[500px] bg-gray-100 dark:bg-gray-900">

                        @if ($previewUrl)

                            @php
                                $extension = strtolower(
                                    pathinfo(
                                        $documentRecord->latestVersion?->file_path,
                                        PATHINFO_EXTENSION
                                    )
                                );
                            @endphp

                            @if ($extension === 'pdf')

                                <iframe
                                    src="{{ $previewUrl }}"
                                    class="h-full w-full border-0"
                                    title="Document Preview"
                                ></iframe>

                            @elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp']))

                                <div class="flex h-full items-center justify-center overflow-auto p-4">
                                    <img
                                        src="{{ $previewUrl }}"
                                        alt="Document Preview"
                                        class="max-h-full max-w-full object-contain"
                                    >
                                </div>

                            @else

                                <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center">

                                    <p class="font-medium text-gray-700 dark:text-gray-300">
                                        Preview is not available for this file type.
                                    </p>

                                    <a
                                        href="{{ $previewUrl }}"
                                        target="_blank"
                                        class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400"
                                    >
                                        Open document
                                    </a>

                                </div>

                            @endif

                        @else

                            <div class="flex h-full items-center justify-center p-6 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    No document file available.
                                </p>
                            </div>

                        @endif

                    </div>

                </div>

            </div>

            {{-- RIGHT: DETAILS --}}
            <div>

                <div
                    class="rounded-xl border border-gray-200
                           bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >

                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Document Information
                        </h2>
                    </div>

                    <div class="space-y-5 p-5">

                        {{-- LAO NUMBER --}}
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400">
                                LAO Number
                            </p>

                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $documentRecord->lao_number ?: 'Not yet assigned' }}
                            </p>
                        </div>

                        {{-- DOCUMENT TYPE --}}
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400">
                                Document Type
                            </p>

                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $documentRecord->type?->type_name ?? 'N/A' }}
                            </p>
                        </div>

                        {{-- PARTICULARS --}}
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400">
                                Particulars
                            </p>

                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $documentRecord->particulars ?: 'N/A' }}
                            </p>
                        </div>

                        {{-- STATUS --}}
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400">
                                Status
                            </p>

                            <p class="mt-1 text-sm font-medium {{ ($requestStatus ?? $documentRecord->status) === 'rejected' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $requestStatus ?? $documentRecord->status
                                    )
                                ) }}
                            </p>
                        </div>

                        {{-- REJECTION REASON --}}
                        @if ($documentRecord->status === 'rejected')
                            @php
                                $rejectionReason =
                                    $documentRecord->rejections->first()?->reason
                                    ?? $documentRecord->rejection_reason;
                            @endphp

                            <div>
                                <p class="text-xs font-bold uppercase text-red-600 dark:text-red-400">
                                    Reason for Rejection
                                </p>

                                <p class="mt-1 whitespace-pre-line text-sm text-red-700 dark:text-red-400">
                                    {{ $rejectionReason ?: 'No rejection reason provided.' }}
                                </p>
                            </div>
                        @endif

                        {{-- DATE SUBMITTED --}}
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400">
                                Date Submitted
                            </p>

                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $documentRecord->created_at?->format('M d, Y h:i A') }}
                            </p>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-filament-panels::page>
