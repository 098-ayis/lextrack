<x-filament-panels::page>

    <div class="mx-auto w-full max-w-4xl">

        <div class="space-y-6">

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
                                    referrerpolicy="no-referrer"
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


                        {{-- PROFILE DETAILS --}}
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
                                Profile photo managed by your account.
                            </p>

                        </div>

                    </div>

                </div>


                {{-- ACCOUNT DETAILS --}}
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
                            value="{{ $name }}"
                            readonly
                            class="block w-full rounded-lg
                                   border-gray-300 bg-gray-50
                                   text-sm text-gray-900 shadow-sm
                                   dark:border-gray-600
                                   dark:bg-gray-800
                                   dark:text-white"
                        >

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
                            value="{{ $email }}"
                            readonly
                            class="block w-full rounded-lg
                                   border-gray-300 bg-gray-50
                                   text-sm text-gray-900 shadow-sm
                                   dark:border-gray-600
                                   dark:bg-gray-800
                                   dark:text-white"
                        >

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
                            value="{{ auth()->user()->getRoleNames()->join(', ') ?? 'User' }}"
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


            </div>

        </div>

    </div>

</x-filament-panels::page>
