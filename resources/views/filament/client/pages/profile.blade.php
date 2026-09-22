<x-filament-panels::page>
    <div class="profile-page mx-auto w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="profile-page-header px-6 py-6">
            <h1 class="text-2xl font-semibold tracking-tight text-[#6366F1]">
                Personal Information
            </h1>
        </div>

        <div class="border-b border-gray-200 px-6 py-6 dark:border-gray-700">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="shrink-0">
                    @if ($this->getProfilePhotoUrl())
                        <img
                            src="{{ $this->getProfilePhotoUrl() }}"
                            alt="Profile photo"
                            referrerpolicy="no-referrer"
                            class="h-24 w-24 rounded-full border-4 border-white object-cover shadow dark:border-gray-800"
                        >
                    @else
                        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                            <x-heroicon-o-user class="h-12 w-12" />
                        </div>
                    @endif
                </div>

                <div class="min-w-0">
                    <p class="truncate text-base font-semibold text-gray-900 dark:text-white">
                        {{ $name }}
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit="save" class="space-y-7 p-6">
            <div>
                <label for="office" class="profile-field-label">
                    Office / Unit <span class="text-red-500">*</span>
                </label>
                <input
                    id="office"
                    type="text"
                    wire:model="office"
                    maxlength="255"
                    required
                    placeholder="Enter your office or unit"
                    class="profile-field-input"
                >
                @error('office')
                    <p class="profile-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="profile-field-label">
                    Email Address
                </label>
                <input
                    id="email"
                    type="email"
                    value="{{ $email }}"
                    readonly
                    class="profile-field-input profile-field-input-readonly"
                >
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="profile-primary-button"
                >
                    <span wire:loading.remove wire:target="save">Save Information</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>

    @include('filament.partials.profile-styles')
</x-filament-panels::page>
