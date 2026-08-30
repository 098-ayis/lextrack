<div
    class="flex items-center justify-between gap-3 border-b border-gray-200
           px-4 py-3"
>
    <h2 class="text-sm font-bold text-gray-950">
        Notes
    </h2>

    {{ ($this->addNoteAction)([
        'document' => $documentRecord->document_id,
    ]) }}
</div>

<div class="p-3">
    @forelse ($documentRecord->notes as $note)
        <div
            class="relative mb-3 overflow-visible rounded-lg border border-gray-200
                   bg-gray-50 last:mb-0"
            x-data="{ menuOpen: false }"
        >
            <div
                class="flex items-center justify-between border-b
                       border-gray-200 px-3 py-2.5"
            >
                <div class="flex min-w-0 items-center gap-3">
                    @if ($note->user && $note->user->profile_photo_url)
                        <img
                            src="{{ $note->user->profile_photo_url }}"
                            alt="{{ $note->user->name ?? 'User' }}"
                            class="h-8 w-8 shrink-0 rounded-full object-cover"
                        >
                    @else
                        <div
                            class="flex h-8 w-8 shrink-0 items-center
                                   justify-center rounded-full bg-blue-100
                                   text-xs font-bold text-blue-700"
                        >
                            {{ strtoupper(substr($note->user->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif

                    <span class="truncate text-xs font-bold text-gray-950">
                        {{ $note->user->name ?? 'User' }}
                    </span>
                </div>

                <div class="relative shrink-0">
                    <button
                        type="button"
                        class="flex h-7 w-7 items-center justify-center
                               rounded-full text-gray-500 hover:bg-gray-100"
                        aria-label="Note options"
                        aria-haspopup="menu"
                        @click.stop="menuOpen = !menuOpen"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6.5h.01M12 12h.01M12 17.5h.01"
                            />
                        </svg>
                    </button>

                    <div
                        x-cloak
                        x-show="menuOpen"
                        x-on:click.outside="menuOpen = false"
                        class="absolute right-0 top-8 z-10 w-28 rounded-md border
                               border-gray-200 bg-white p-1 shadow-lg"
                        role="menu"
                    >
                        {{ ($this->editNoteAction)([
                            'note' => $note->note_id,
                        ]) }}

                        {{ ($this->deleteNoteAction)([
                            'note' => $note->note_id,
                        ]) }}
                    </div>
                </div>
            </div>

            <div class="px-3 py-2.5">
                <p class="text-xs leading-5 text-gray-600">
                    {{ $note->body ?? $note->note ?? '' }}
                </p>
            </div>
        </div>
    @empty
        <div
            class="rounded-lg border border-dashed border-gray-300
                   px-3 py-5 text-center"
        >
            <p class="text-xs text-gray-400">
                No notes for this document.
            </p>
        </div>
    @endforelse
    </div>
