<x-filament-panels::page>

    <div class="w-full flex flex-col items-center min-h-[80vh] pt-10 pb-16 px-6">

        {{-- Main Tracking Card --}}
        <div class="w-full max-w-3xl overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-[0_10px_40px_rgba(0,0,0,0.08)] mb-6 dark:border-gray-700 dark:bg-[#17181c] dark:shadow-[0_10px_40px_rgba(0,0,0,0.25)]">

            {{-- Header --}}
            <div class="bg-[#121722] px-6 py-5 md:px-8 md:py-6">
                <h2 class="text-lg md:text-xl font-bold text-[#828cff] mb-1">
                    Track your Document
                </h2>

                <p class="text-gray-300 text-xs md:text-sm">
                    Enter the tracking number of your request.
                </p>
            </div>

            {{-- Form --}}
            <div class="p-6 md:p-8">

                <form wire:submit="trackDocument">

                    <label class="text-sm font-semibold text-[#121722] dark:text-gray-100">
                        Tracking Number
                        <span class="text-red-500">*</span>
                    </label>

                    <div class="flex flex-col md:flex-row gap-3 mt-2">

                        <input
                            type="text"
                            wire:model="trackingNumber"
                            placeholder="e.g. LAO-26-6767"
                            class="h-[50px] flex-1 rounded-xl border border-gray-200 bg-white px-4 text-sm font-medium text-[#334155] outline-none transition-all placeholder-gray-400 focus:border-[#828cff] focus:ring-2 focus:ring-[#828cff]/20 dark:border-gray-600 dark:bg-[#20242d] dark:text-gray-100 dark:placeholder:text-gray-400"
                        >

                        <button
                            type="submit"
                            class="h-[50px] rounded-xl bg-[#6b77ff] px-8 text-sm font-bold tracking-wider text-white shadow-sm transition-all hover:bg-[#828cff] hover:shadow-md"
                        >
                            TRACK
                        </button>

                    </div>

                    @error('trackingNumber')
                        <p class="text-red-500 text-xs mt-2">
                            {{ $message }}
                        </p>
                    @enderror

                </form>

            </div>
        </div>

        {{-- Result --}}
        @if ($hasSearched && $document)

            <div class="w-full max-w-6xl mt-6">

                <div class="overflow-x-auto rounded-2xl border border-gray-300 bg-white dark:border-gray-700 dark:bg-[#17181c]">

                    <table class="w-full min-w-[850px] border-collapse">

                        {{-- HEADER --}}
                        <thead class="bg-[#174F78]">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wide text-white">
                                    LAO #
                                </th>

                                <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wide text-white">
                                    Document Type
                                </th>

                                <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wide text-white">
                                    Particulars
                                </th>

                                <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wide text-white">
                                    Date Submitted
                                </th>

                                <th class="px-6 py-3 text-center text-sm font-bold uppercase tracking-wide text-white">
                                    Status
                                </th>
                            </tr>
                        </thead>

                        {{-- DOCUMENT --}}
                        <tbody>
                            <tr class="bg-white dark:bg-[#17181c]">

                                {{-- LAO NUMBER --}}
                                <td class="px-6 py-5">
                                    <span class="text-base font-bold text-gray-900 dark:text-gray-100">
                                        {{ $document->lao_number }}
                                    </span>
                                </td>

                                {{-- DOCUMENT TYPE --}}
                                <td class="px-6 py-5">
                                    <span class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $document->type?->type_name ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- PARTICULARS --}}
                                <td class="px-6 py-5">
                                    <span class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $document->particulars }}
                                    </span>
                                </td>

                                {{-- DATE SUBMITTED --}}
                                <td class="px-6 py-5">
                                    <span class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $document->created_at?->format('F d, Y') ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- STATUS --}}
                                <td class="px-6 py-5 text-center">

                                    @php
                                        $statusClasses = match ($document->status) {
                                            'pending' =>
                                                'border-yellow-400 bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',

                                            'in_progress' =>
                                                'border-blue-400 bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',

                                            'completed' =>
                                                'border-green-500 bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',

                                            'rejected' =>
                                                'border-red-400 bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',

                                            'outgoing' =>
                                                'border-purple-400 bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300',

                                            'returned' =>
                                                'border-orange-400 bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300',

                                            'archived' =>
                                                'border-gray-400 bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',

                                            default =>
                                                'border-gray-300 bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                        };

                                        $statusLabel = match ($document->status) {
                                            'in_progress' => 'In Progress',
                                            'completed' => 'Completed',
                                            'pending' => 'Pending',
                                            'rejected' => 'Rejected',
                                            'outgoing' => 'Outgoing',
                                            'returned' => 'Returned',
                                            'archived' => 'Archived',
                                            default => ucfirst(
                                                str_replace('_', ' ', $document->status)
                                            ),
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex min-w-[120px] items-center justify-center
                                            rounded-full border px-5 py-1
                                            text-sm font-bold {{ $statusClasses }}"
                                    >
                                        {{ $statusLabel }}
                                    </span>

                                </td>

                            </tr>
                        </tbody>

                    </table>

                </div>

            </div>

        @elseif ($hasSearched)

            <div
                class="w-full max-w-3xl mt-6 rounded-2xl
                    border border-gray-100 bg-white
                    px-6 py-8 text-center
                    shadow-[0_4px_20px_rgba(0,0,0,0.04)]
                    dark:border-gray-700 dark:bg-[#17181c] dark:shadow-[0_4px_20px_rgba(0,0,0,0.2)]"
            >
                <p class="text-sm font-medium text-gray-400 dark:text-gray-300">
                    No document found
                </p>
            </div>

        @endif

    </div>

</x-filament-panels::page>
