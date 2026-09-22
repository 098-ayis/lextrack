<div
    class="flex items-center justify-between gap-3 border-b border-gray-200
           px-4 py-3"
>
    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">
        Notes
    </p>

    @if ($isAddingNote)
        <button
            type="button"
            wire:click="cancelAddingNote"
            class="add-note-button"
            aria-label="Close new note form"
            title="Close"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    @else
        <button
            type="button"
            wire:click="startAddingNote"
            class="add-note-button"
            aria-label="Add note"
            title="Add note"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
            </svg>
        </button>
    @endif
</div>

<div class="p-3">
    @if ($isAddingNote)
        <form wire:submit.prevent="saveNote" class="mb-3 rounded-lg border border-gray-200 bg-white p-3">
            <label for="new-document-note" class="text-xs font-semibold text-gray-700">New note</label>
            <textarea
                id="new-document-note"
                wire:model="newNoteText"
                rows="4"
                maxlength="5000"
                placeholder="Write a note about this document..."
                class="mt-2 w-full resize-y rounded-md border border-gray-300 bg-white px-3 py-2 text-xs leading-5 text-gray-800 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
            ></textarea>
            @error('newNoteText')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
            <div class="mt-2 flex justify-end gap-2">
                <button
                    type="button"
                    wire:click="cancelAddingNote"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                >Cancel</button>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="document-note-action-button rounded-md px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
                >Save Note</button>
            </div>
        </form>
    @endif

    @forelse ($documentRecord->notes->sortByDesc('created_at')->values() as $note)
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
                    @if ($note->user && $note->user->getProfilePhotoUrl())
                        <img
                            src="{{ $note->user->getProfilePhotoUrl() }}"
                            alt="{{ $note->user->name ?? 'User' }}"
                            referrerpolicy="no-referrer"
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

                    <div class="flex min-w-0 flex-col">
                        <span class="document-note-author truncate text-xs font-bold text-gray-950">
                            {{ $note->user->name ?? 'User' }}
                        </span>
                        @if ($note->created_at)
                            <time
                                datetime="{{ $note->created_at->toIso8601String() }}"
                                class="document-note-timestamp text-[10px] font-medium text-gray-500"
                            >{{ $note->created_at->format('m/d/Y | g:i A') }}</time>
                        @endif
                    </div>
                </div>

                @if ($editingNoteId === null)
                    <div class="document-note-actions relative shrink-0">
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center
                                   rounded-full bg-transparent
                                   text-gray-900 hover:bg-gray-100"
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
                                    stroke-width="2.6"
                                    d="M12 6.5h.01M12 12h.01M12 17.5h.01"
                                />
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="menuOpen"
                            x-on:click.outside="menuOpen = false"
                            class="absolute right-0 top-8 z-10 flex w-24 flex-col gap-1
                                   rounded-md border border-gray-200 bg-white p-1 shadow-lg"
                            role="menu"
                        >
                            <button
                                type="button"
                                wire:click="startEditingNote({{ $note->note_id }})"
                                @click.stop="menuOpen = false"
                                class="flex w-full items-center rounded-md px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-100"
                                role="menuitem"
                            >
                                Edit
                            </button>

                            {{ ($this->deleteNoteAction)([
                                'note' => $note->note_id,
                            ]) }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="px-3 py-2.5">
                @if ($editingNoteId === (int) $note->note_id)
                    <form wire:submit.prevent="saveNoteEdit" class="space-y-2">
                        <textarea
                            wire:model="editingNoteText"
                            rows="4"
                            maxlength="5000"
                            aria-label="Edit note"
                            class="w-full resize-y rounded-md border border-gray-300 bg-white px-3 py-2 text-xs leading-5 text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        ></textarea>
                        @error('editingNoteText')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                wire:click="cancelEditingNote"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                            >Cancel</button>
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="document-note-action-button rounded-md px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
                            >Save Changes</button>
                        </div>
                    </form>
                @else
                    <p class="document-note-content text-xs leading-5 text-gray-600">
                        {{ $note->body ?? $note->note ?? '' }}
                    </p>
                @endif
            </div>
        </div>
    @empty
        @unless ($isAddingNote)
            <div
                class="rounded-lg border border-dashed border-gray-300
                       px-3 py-5 text-center"
            >
                <p class="document-note-empty text-xs text-gray-400">
                    No notes for this document.
                </p>
            </div>
        @endunless
    @endforelse
    </div>
