<x-filament-panels::page>
    
    <!-- Custom Tabs Container (Tailwind Only) -->
    <div class="inline-flex flex-wrap items-center gap-1 rounded-lg border border-gray-200 bg-white p-1.5 shadow-sm mb-4 dark:border-gray-700 dark:bg-gray-800">

        <!-- All Tab (Default) -->
        <button
            wire:click="updateTab('all')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'all' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            All
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'all' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->count() }}
            </span>
        </button>

        <!-- Pending Tab -->
        <button 
            wire:click="updateTab('pending')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'pending' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            Pending
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'pending' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->where('status', 'pending')->count() }}
            </span>
        </button>

        <!-- In Progress Tab -->
        <button 
            wire:click="updateTab('in_progress')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'in_progress' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            In Progress
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'in_progress' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->whereIn('status', ['in_progress', 'outgoing'])->count() }}
            </span>
        </button>

        <!-- Completed Tab -->
        <button
            wire:click="updateTab('completed')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'completed' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            Completed
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'completed' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->whereIn('status', ['completed', 'archived'])->count() }}
            </span>
        </button>

        <!-- Rejected Tab -->
        <button 
            wire:click="updateTab('rejected')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'rejected' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            Rejected
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'rejected' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->where('status', 'rejected')->count() }}
            </span>
        </button>

        <!-- Requested Tab -->
        <button 
            wire:click="updateTab('requested')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'requested' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            Requested
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'requested' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->where('status', 'requested')->count() }}
            </span>
        </button>
        
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex w-full flex-col gap-3 sm:flex-row sm:items-center">

        <!-- Search -->
        <div class="relative w-full sm:max-w-md sm:flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <svg
                    class="h-5 w-5 text-gray-400"
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
                class="block w-full rounded-full border border-gray-300 bg-white py-2.5 pl-11 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#6366F1] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
            >
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-2">

            <!-- Type -->
            <div class="flex items-center gap-1.5">
                <div class="relative w-44">
                    <select
                        wire:model.live="documentType"
                        class="w-full appearance-none rounded-lg border-2 py-2 pl-3 pr-9 text-xs font-semibold text-gray-900 focus:outline-none focus:ring-0 dark:text-gray-100 {{ $documentType ? 'border-[#6366F1] bg-[#F0F1FF] dark:border-indigo-400 dark:bg-indigo-950' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800' }}"
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
                        class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 {{ $documentType ? 'text-[#6366F1] dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

                @if ($documentType)
                    <button
                        type="button"
                        wire:click="clearType"
                        class="flex h-[34px] w-[34px] items-center justify-center rounded-lg border-2 border-[#6366F1] bg-[#F0F1FF] text-[#6366F1] hover:bg-[#E4E5FF] dark:bg-indigo-950 dark:text-indigo-400 dark:hover:bg-indigo-900"
                        title="Clear type"
                    >
                        <svg
                            class="h-4 w-4"
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

            @if ($activeTab === 'requested')
                <!-- Status -->
                <div class="flex items-center gap-1.5">
                    <div class="relative w-36">
                        <select
                            wire:model.live="documentStatus"
                            class="w-full appearance-none rounded-lg border-2 py-2 pl-3 pr-9 text-xs font-semibold text-gray-900 focus:outline-none focus:ring-0 dark:text-gray-100 {{ $documentStatus ? 'border-[#6366F1] bg-[#F0F1FF] dark:border-indigo-400 dark:bg-indigo-950' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800' }}"
                        >
                            <option value="">Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>

                        <svg
                            class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 {{ $documentStatus ? 'text-[#6366F1] dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>

                    @if ($documentStatus)
                        <button
                            type="button"
                            wire:click="clearStatus"
                            class="flex h-[34px] w-[34px] items-center justify-center rounded-lg border-2 border-[#6366F1] bg-[#F0F1FF] text-[#6366F1] hover:bg-[#E4E5FF] dark:bg-indigo-950 dark:text-indigo-400 dark:hover:bg-indigo-900"
                            title="Clear status"
                        >
                            <svg
                                class="h-4 w-4"
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
            @endif

        </div>
    </div>

    <!-- Render the data table below the custom tabs -->
    {{ $this->table }}

</x-filament-panels::page>
