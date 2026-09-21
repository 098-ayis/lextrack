<x-filament-panels::page>
    <div class="client-request-page w-full overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="request-page-header px-6 py-6">
            <h1 class="text-2xl font-semibold tracking-tight text-[#6366F1]">
                Request A Document
            </h1>
            <p class="mt-1 text-sm text-gray-100">
                Select what you need and tell us more about your request.
            </p>
        </div>

        <form wire:submit="submit" class="p-6">
            <div class="w-full space-y-7">
                <div>
                    <label for="request-purpose" class="request-field-label">
                        Purpose <span class="text-red-500">*</span>
                    </label>
                    @if ($purpose === 'other')
                        <div class="relative">
                            <input
                                id="request-purpose-other"
                                type="text"
                                wire:model.blur="purposeOther"
                                placeholder="Type the purpose you need..."
                                class="request-field-input pr-12"
                                autofocus
                            >
                            <button
                                type="button"
                                wire:click="clearPurpose"
                                class="request-select-clear absolute right-4 top-1/2 -translate-y-1/2"
                                aria-label="Clear purpose"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
                                </svg>
                            </button>
                        </div>
                    @else
                        <div
                            class="request-select-wrapper"
                            x-data="{ open: false, search: '' }"
                            x-on:click.outside="open = false"
                        >
                        <button
                            id="request-purpose"
                            type="button"
                            class="request-select-trigger"
                            x-on:click="open = !open"
                            x-bind:aria-expanded="open"
                            aria-haspopup="listbox"
                        >
                            <span class="{{ $purpose ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400 dark:text-gray-400' }}">
                                {{ $purpose ? ($this->purposeOptions()[$purpose] ?? $purpose) : 'Select a purpose' }}
                            </span>
                            <span class="flex items-center gap-2">
                                @if ($purpose)
                                    <span
                                        class="request-select-clear"
                                        role="button"
                                        tabindex="0"
                                        x-on:click.stop="$wire.set('purpose', ''); open = false; search = ''"
                                        x-on:keydown.enter.stop="$wire.set('purpose', ''); open = false; search = ''"
                                        aria-label="Clear purpose"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
                                        </svg>
                                    </span>
                                @endif
                                <svg
                                    class="request-select-chevron"
                                    x-bind:class="{ 'request-select-chevron-open': open }"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </span>
                        </button>

                        <div
                            x-cloak
                            x-show="open"
                            x-transition.origin.top
                            class="request-select-panel"
                            role="listbox"
                        >
                            <input
                                type="search"
                                x-model="search"
                                x-on:keydown.escape="open = false"
                                placeholder="Start typing to search..."
                                class="request-select-search"
                            >
                            <div class="request-select-options">
                                @foreach ($this->purposeOptions() as $value => $label)
                                    <button
                                        type="button"
                                        x-show="!search || {{ \Illuminate\Support\Js::from(strtolower($label)) }}.includes(search.toLowerCase())"
                                        x-on:click="$wire.set('purpose', {{ \Illuminate\Support\Js::from($value) }}); open = false; search = ''"
                                        class="request-select-option"
                                        role="option"
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        </div>
                    @endif
                    @error('purpose')
                        <p class="request-error">{{ $message }}</p>
                    @enderror
                    @error('purposeOther')
                        <p class="request-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="request-purpose-details" class="request-field-label">
                        Specify your purpose <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="request-purpose-details"
                        wire:model.live="purposeDetails"
                        rows="6"
                        maxlength="200"
                        placeholder="Please explain what you need this document for..."
                        class="request-field-input resize-y"
                    ></textarea>
                    <p class="mt-1 text-right text-xs text-gray-500 dark:text-gray-400">
                        {{ mb_strlen($purposeDetails) }}/200 characters
                    </p>
                    @error('purposeDetails')
                        <p class="request-error">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset>
                    <legend class="request-field-label">
                        Copy type <span class="text-red-500">*</span>
                    </legend>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($this->copyTypeOptions() as $value => $label)
                            <label
                                class="request-radio-option {{ $copyType === $value ? 'request-radio-option-selected' : '' }}"
                            >
                                <input
                                    type="radio"
                                    name="copyType"
                                    value="{{ $value }}"
                                    wire:model.live="copyType"
                                    class="request-radio-input"
                                >
                                <span>
                                    <span class="block font-semibold">{{ $label }}</span>
                                    <span class="mt-1 block text-sm font-normal text-gray-500 dark:text-gray-400">
                                        {{ $value === 'original' ? 'The office will set the pickup schedule.' : 'Receive a digital copy online.' }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @error('copyType')
                        <p class="request-error">{{ $message }}</p>
                    @enderror
                </fieldset>

            </div>

            <div class="mt-10 flex justify-end gap-3 sm:gap-4">
                <button
                    type="button"
                    wire:click="clearForm"
                    class="request-clear-button"
                >
                    Clear
                </button>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                    class="request-primary-button"
                >
                    <span wire:loading.remove wire:target="submit">Submit</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>
            </div>
        </form>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .client-request-page {
            --request-indigo: #6366f1;
        }

        .request-page-header {
            background: #0f172a;
        }

        .request-field-label {
            display: block;
            margin-bottom: 0.6rem;
            color: #164e77;
            font-size: 1rem;
            font-weight: 700;
        }

        .dark .request-field-label {
            color: #f3f4f6;
        }

        .request-field-input {
            display: block;
            width: 100%;
            border: 1px solid #9ca3af;
            border-radius: 0.75rem;
            background: #ffffff;
            color: #111827;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
        }

        .request-field-input::placeholder {
            color: #9ca3af;
        }

        .request-field-input:focus {
            border-color: var(--request-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .request-select-wrapper {
            position: relative;
        }

        .request-select-trigger {
            display: flex;
            width: 100%;
            min-height: 2.75rem;
            cursor: pointer;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            border: 1px solid #9ca3af;
            border-radius: 0.75rem;
            background: #ffffff;
            padding: 0.85rem 1rem;
            color: #36384a;
            font-size: 0.875rem;
            text-align: left;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .request-select-trigger:hover,
        .request-select-trigger[aria-expanded='true'] {
            border-color: #a995f4;
            box-shadow: 0 0 0 3px rgba(216, 204, 255, 0.45);
        }

        .request-select-chevron {
            width: 1.1rem;
            height: 1.1rem;
            flex-shrink: 0;
            color: #6b7280;
            transition: transform 150ms ease;
        }

        .request-select-clear {
            display: inline-flex;
            width: 1.1rem;
            height: 1.1rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9999px;
            background: transparent;
            color: #9ca3af;
        }

        .request-select-clear:hover {
            background: #ede9fe;
            color: #4f46e5;
        }

        .request-select-clear svg {
            width: 0.9rem;
            height: 0.9rem;
        }

        .request-select-chevron-open {
            transform: rotate(180deg);
        }

        .request-select-panel {
            position: absolute;
            z-index: 30;
            top: calc(100% + 0.35rem);
            left: 0;
            width: 100%;
            overflow: hidden;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background: #ffffff;
            box-shadow: 0 16px 35px rgba(15, 23, 42, 0.16);
        }

        .request-select-search {
            display: block;
            width: 100%;
            height: 2.75rem;
            border: 0;
            border-bottom: 2px solid #d8ccff;
            background: #ffffff;
            padding: 0.65rem 1rem;
            color: #111827;
            font-size: 0.875rem;
            outline: none;
        }

        .request-select-search::placeholder {
            color: #a1a1b0;
        }

        .request-select-options {
            max-height: 18rem;
            overflow-y: auto;
            padding: 0.35rem 0;
        }

        .request-select-option {
            display: block;
            width: 100%;
            min-height: 2.5rem;
            border: 0;
            background: #ffffff;
            padding: 0.6rem 1rem;
            color: #111827;
            font-size: 0.875rem;
            text-align: left;
            transition: background-color 150ms ease, color 150ms ease;
        }

        .request-select-option:hover {
            background: #f0edff;
            color: #4f46e5;
        }

        .request-radio-option {
            display: flex;
            min-height: 5.5rem;
            cursor: pointer;
            align-items: flex-start;
            gap: 0.75rem;
            border: 1px solid #9ca3af;
            border-radius: 0.75rem;
            background: #ffffff;
            padding: 1rem;
            color: #111827;
            transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
        }

        .request-radio-option:hover {
            border-color: var(--request-indigo);
            background: #f0f1ff;
        }

        .request-radio-option-selected {
            border-color: var(--request-indigo);
            background: #f0f1ff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.16);
        }

        .request-radio-input {
            width: 1.1rem;
            height: 1.1rem;
            margin-top: 0.1rem;
            flex-shrink: 0;
            accent-color: var(--request-indigo);
        }

        select.request-field-input {
            appearance: none;
            cursor: pointer;
            padding-right: 2.75rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='1.8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m5 7.5 5 5 5-5'/%3E%3C/svg%3E");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1rem;
        }

        .dark .request-field-input {
            border-color: #4b5563;
            background: #1f2937;
            color: #f9fafb;
        }

        .dark .request-select-trigger,
        .dark .request-select-panel,
        .dark .request-select-search,
        .dark .request-select-option {
            border-color: #4b5563;
            background: #1f2937;
            color: #f9fafb;
        }

        .dark .request-select-trigger:hover,
        .dark .request-select-trigger[aria-expanded='true'] {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25);
        }

        .dark .request-select-search {
            border-bottom-color: #818cf8;
        }

        .dark .request-select-clear {
            color: #9ca3af;
        }

        .dark .request-select-clear:hover {
            background: #4338ca;
            color: #e0e7ff;
        }

        .dark .request-select-option:hover {
            background: #312e81;
            color: #e0e7ff;
        }

        .dark .request-radio-option {
            border-color: #4b5563;
            background: #1f2937;
            color: #f9fafb;
        }

        .dark .request-radio-option:hover,
        .dark .request-radio-option-selected {
            border-color: #818cf8;
            background: #312e81;
        }

        .dark select.request-field-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%23d1d5db' stroke-width='1.8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m5 7.5 5 5 5-5'/%3E%3C/svg%3E");
        }

        .dark .request-field-input:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25);
        }

        .dark .request-field-input option {
            background: #1f2937;
            color: #f9fafb;
        }

        .request-primary-button,
        .request-clear-button {
            display: inline-flex;
            min-height: 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, opacity 150ms ease;
        }

        .request-primary-button {
            border: 1px solid var(--request-indigo);
            background: var(--request-indigo);
            color: #ffffff;
        }

        .request-primary-button:hover {
            background: #4f46e5;
        }

        .request-clear-button {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
        }

        .request-clear-button:hover {
            background: #f9fafb;
        }

        .dark .request-clear-button {
            border-color: #4b5563;
            background: #1f2937;
            color: #e5e7eb;
        }

        .dark .request-clear-button:hover {
            background: #374151;
        }

        .request-error {
            margin-top: 0.35rem;
            color: #dc2626;
            font-size: 0.875rem;
        }

        .request-primary-button:disabled,
        .request-clear-button:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }
    </style>
</x-filament-panels::page>
