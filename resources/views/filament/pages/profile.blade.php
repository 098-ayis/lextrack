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

        <div class="space-y-7 p-6">
            <div>
                <label for="admin-profile-name" class="profile-field-label">
                    Full Name
                </label>
                <input
                    id="admin-profile-name"
                    type="text"
                    value="{{ $name }}"
                    readonly
                    class="profile-field-input profile-field-input-readonly"
                >
            </div>

            <div>
                <label for="admin-profile-email" class="profile-field-label">
                    Email Address
                </label>
                <input
                    id="admin-profile-email"
                    type="email"
                    value="{{ $email }}"
                    readonly
                    class="profile-field-input profile-field-input-readonly"
                >
            </div>

            <div>
                <label for="admin-profile-role" class="profile-field-label">
                    Role
                </label>
                <input
                    id="admin-profile-role"
                    type="text"
                    value="{{ auth()->user()->getRoleNames()->join(', ') ?: 'User' }}"
                    readonly
                    class="profile-field-input profile-field-input-readonly"
                >
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Your account role cannot be changed here.
                </p>
            </div>
        </div>
    </div>

    @include('filament.partials.profile-styles')
</x-filament-panels::page>
