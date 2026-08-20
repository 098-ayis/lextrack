<x-filament-panels::page>

    <div class="w-full flex flex-col items-center min-h-[80vh] pt-10 pb-16 px-6">

        {{-- Main Tracking Card --}}
        <div class="w-full max-w-3xl bg-white rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.08)] border border-gray-100 overflow-hidden mb-6">

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

                    <label class="text-sm font-semibold text-[#121722]">
                        Tracking Number
                        <span class="text-red-500">*</span>
                    </label>

                    <div class="flex flex-col md:flex-row gap-3 mt-2">

                        <input
                            type="text"
                            wire:model="trackingNumber"
                            placeholder="e.g. LAO-26-6767"
                            class="flex-1 h-[50px] px-4 bg-white border border-gray-200 rounded-xl outline-none focus:border-[#828cff] focus:ring-2 focus:ring-[#828cff]/20 text-[#334155] font-medium placeholder-gray-400 transition-all text-sm"
                        >

                        <button
                            type="submit"
                            class="h-[50px] px-8 bg-[#6b77ff] hover:bg-[#828cff] text-white font-bold text-sm tracking-wider rounded-xl transition-all shadow-sm hover:shadow-md"
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
            <div class="w-full max-w-3xl bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-gray-100 py-5 px-6 text-center">

                    <div class="text-left">

                        <h3 class="font-bold text-lg text-[#121722]">
                            {{ $document->title }}
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Tracking Number:
                            {{ $document->tracking_number }}
                        </p>

                        <p class="text-sm font-semibold text-[#6b77ff] mt-3">
                            Status: {{ $document->status }}
                        </p>

                    </div>

            </div>

        @elseif ($hasSearched)

            <div class="w-full max-w-3xl bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-gray-100 py-5 px-6 text-center">

                <p class="text-gray-400 text-sm font-medium">
                    No document found
                </p>

        @endif

    </div>

</x-filament-panels::page>