<x-filament-panels::page>
    
    <!-- Custom Tabs Container (Tailwind Only) -->
    <div class="inline-flex flex-wrap items-center gap-1 p-1.5 bg-white border border-gray-200 rounded-lg shadow-sm mb-4">

        <!-- All Tab (Default) -->
        <button
            wire:click="updateTab('all')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-all {{ $activeTab === 'all' ? 'bg-[#0F172A] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
        >
            All
            <span class="flex items-center justify-center px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'all' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->count() }}
            </span>
        </button>

        <!-- Pending Tab -->
        <button 
            wire:click="updateTab('pending')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-all {{ $activeTab === 'pending' ? 'bg-[#0F172A] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
        >
            Pending
            <span class="flex items-center justify-center px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'pending' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->where('status', 'pending')->count() }}
            </span>
        </button>

        <!-- In Progress Tab -->
        <button 
            wire:click="updateTab('in_progress')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-all {{ $activeTab === 'in_progress' ? 'bg-[#0F172A] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
        >
            In Progress
            <span class="flex items-center justify-center px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'in_progress' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->whereIn('status', ['in_progress', 'outgoing'])->count() }}
            </span>
        </button>

        <!-- Completed Tab -->
        <button
            wire:click="updateTab('completed')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-all {{ $activeTab === 'completed' ? 'bg-[#0F172A] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
        >
            Completed
            <span class="flex items-center justify-center px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'completed' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->whereIn('status', ['completed', 'archived'])->count() }}
            </span>
        </button>

        <!-- Rejected Tab -->
        <button 
            wire:click="updateTab('rejected')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-all {{ $activeTab === 'rejected' ? 'bg-[#0F172A] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
        >
            Rejected
            <span class="flex items-center justify-center px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'rejected' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->where('status', 'rejected')->count() }}
            </span>
        </button>

        <!-- Requested Tab -->
        <button 
            wire:click="updateTab('requested')"
            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-all {{ $activeTab === 'requested' ? 'bg-[#0F172A] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
        >
            Requested
            <span class="flex items-center justify-center px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'requested' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->where('status', 'requested')->count() }}
            </span>
        </button>
        
    </div>

    <!-- Render the data table below the custom tabs -->
    {{ $this->table }}

</x-filament-panels::page>
