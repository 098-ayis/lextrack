<x-filament-panels::page>

    <div class="mx-auto w-full max-w-4xl">

        <form
            wire:submit="save"
            class="space-y-6"
        >

            {{-- PROFILE CARD --}}
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm
                       dark:border-gray-700 dark:bg-gray-900"
            >

                {{-- HEADER --}}
                <div
                    class="border-b border-gray-200 px-6 py-5
                           dark:border-gray-700"
                >
                    <h2
                        class="text-xl font-bold text-gray-900
                               dark:text-white"
                    >
                        Personal Information
                    </h2>

                    <p
                        class="mt-1 text-sm text-gray-500
                               dark:text-gray-400"
                    >
                        Update your photo and personal information.
                    </p>
                </div>


                {{-- PROFILE PHOTO --}}
                <div
                    class="border-b border-gray-200 px-6 py-6
                           dark:border-gray-700"
                >

                    <div
                        class="flex flex-col gap-5
                               sm:flex-row sm:items-center"
                    >

                        {{-- PHOTO --}}
                        <div class="shrink-0">

                            @if ($this->getProfilePhotoUrl())

                                <img
                                    src="{{ $this->getProfilePhotoUrl() }}"
                                    alt="Profile photo"
                                    class="h-24 w-24 rounded-full
                                           border-4 border-white
                                           object-cover shadow
                                           dark:border-gray-800"
                                >

                            @else

                                <div
                                    class="flex h-24 w-24 items-center
                                           justify-center rounded-full
                                           bg-gray-100 text-gray-400
                                           dark:bg-gray-800"
                                >
                                    <x-heroicon-o-user
                                        class="h-12 w-12"
                                    />
                                </div>

                            @endif

                        </div>


                        {{-- UPLOAD --}}
                        <div>

                            <h3
                                class="text-sm font-semibold text-gray-900
                                       dark:text-white"
                            >
                                Profile Photo
                            </h3>

                            <p
                                class="mt-1 text-xs text-gray-500
                                       dark:text-gray-400"
                            >
                                JPG, PNG or WEBP. Maximum file size: 2 MB.
                            </p>


                            <div class="mt-3">

                                <label
                                    class="inline-flex cursor-pointer
                                           items-center gap-2 rounded-lg
                                           border border-gray-300
                                           bg-white px-4 py-2
                                           text-sm font-semibold
                                           text-gray-700 shadow-sm
                                           transition
                                           hover:bg-gray-50
                                           dark:border-gray-600
                                           dark:bg-gray-800
                                           dark:text-gray-200
                                           dark:hover:bg-gray-700"
                                >

                                    <x-heroicon-o-camera
                                        class="h-5 w-5"
                                    />

                                    Change Photo

                                    <input
                                        type="file"
                                        wire:model="photo"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="hidden"
                                    >

                                </label>

                            </div>


                            <div
                                wire:loading
                                wire:target="photo"
                                class="mt-2 text-xs text-gray-500"
                            >
                                Uploading photo...
                            </div>


                            @error('photo')

                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                    </div>

                </div>


                {{-- FORM --}}
                <div class="space-y-6 px-6 py-6">

                    {{-- NAME --}}
                    <div>

                        <label
                            for="name"
                            class="mb-2 block text-sm font-semibold
                                   text-gray-700 dark:text-gray-200"
                        >
                            Full Name
                        </label>

                        <input
                            id="name"
                            type="text"
                            wire:model="name"
                            class="block w-full rounded-lg
                                   border-gray-300 bg-white
                                   text-sm text-gray-900 shadow-sm
                                   focus:border-primary-500
                                   focus:ring-primary-500
                                   dark:border-gray-600
                                   dark:bg-gray-800
                                   dark:text-white"
                        >

                        @error('name')

                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    {{-- EMAIL --}}
                    <div>

                        <label
                            for="email"
                            class="mb-2 block text-sm font-semibold
                                   text-gray-700 dark:text-gray-200"
                        >
                            Email Address
                        </label>

                        <input
                            id="email"
                            type="email"
                            wire:model="email"
                            class="block w-full rounded-lg
                                   border-gray-300 bg-white
                                   text-sm text-gray-900 shadow-sm
                                   focus:border-primary-500
                                   focus:ring-primary-500
                                   dark:border-gray-600
                                   dark:bg-gray-800
                                   dark:text-white"
                        >

                        @error('email')

                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    {{-- ROLE --}}
                    <div>

                        <label
                            class="mb-2 block text-sm font-semibold
                                   text-gray-700 dark:text-gray-200"
                        >
                            Role
                        </label>

                        <input
                            type="text"
                            value="{{ auth()->user()->role_name ?? 'User' }}"
                            disabled
                            class="block w-full cursor-not-allowed
                                   rounded-lg border-gray-300
                                   bg-gray-100 text-sm text-gray-500
                                   shadow-sm
                                   dark:border-gray-600
                                   dark:bg-gray-800
                                   dark:text-gray-400"
                        >

                        <p class="mt-1 text-xs text-gray-500">
                            Your account role cannot be changed here.
                        </p>

                    </div>

                </div>


                {{-- FOOTER --}}
                <div
                    class="flex items-center justify-end
                           border-t border-gray-200
                           bg-gray-50 px-6 py-4
                           dark:border-gray-700
                           dark:bg-gray-800/50"
                >

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save,photo"
                        class="inline-flex items-center gap-2
                               rounded-lg bg-primary-600
                               px-5 py-2.5 text-sm
                               font-semibold text-white
                               shadow-sm transition
                               hover:bg-primary-500
                               disabled:cursor-not-allowed
                               disabled:opacity-50"
                    >

                        <x-heroicon-o-check
                            class="h-5 w-5"
                        />

                        <span
                            wire:loading.remove
                            wire:target="save"
                        >
                            Save Changes
                        </span>

                        <span
                            wire:loading
                            wire:target="save"
                        >
                            Saving...
                        </span>

                    </button>

                </div>

            </div>

        </form>

    </div>

</x-filament-panels::page>