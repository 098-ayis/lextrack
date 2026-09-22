<x-filament-widgets::widget>
    <div class="client-dashboard-welcome relative isolate overflow-hidden rounded-2xl bg-[linear-gradient(115deg,#050816_0%,#101b46_38%,#172b68_72%,#1e3a8a_100%)] shadow-sm">
        <div class="relative z-10 px-6 pb-12 pt-10 sm:px-8 sm:pb-16 sm:pt-12">
            <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Welcome, {{ auth()->user()?->name ?? 'User' }}!
            </h2>

            <p class="mt-2 max-w-2xl text-sm text-indigo-100 sm:text-base">
                Manage your documents, requests, and submissions in one place.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a
                    href="/client/request-document"
                    class="inline-flex w-36 items-center justify-center gap-2 rounded-full border border-white bg-white px-5 py-2.5 text-sm font-semibold text-[#6366F1] transition hover:bg-indigo-50"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7
                               a2 2 0 01-2-2V5
                               a2 2 0 012-2h5.586
                               a1 1 0 01.707.293
                               l3.414 3.414
                               A1 1 0 0117 7.414V19
                               a2 2 0 01-2 2z"
                        />
                    </svg>
                    Request
                </a>

                <a
                    href="/client/upload"
                    class="inline-flex w-36 items-center justify-center gap-2 rounded-full border border-indigo-300 bg-indigo-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-400"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3
                               M6.75 19.5a4.5 4.5 0 01-1.41-8.775
                               5.25 5.25 0 0110.233-2.33
                               3 3 0 013.758 3.848
                               A3.752 3.752 0 0118 19.5H6.75z"
                        />
                    </svg>
                    Submit
                </a>
            </div>
        </div>

        <svg
            class="pointer-events-none absolute bottom-0 left-0 h-16 w-full -scale-x-100 text-indigo-300/25"
            viewBox="0 0 1440 160"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <path
                fill="currentColor"
                d="M0 86C120 12 240 12 360 86S600 160 720 86s240-74 360 0 240 74 360 0v74H0V86Z"
            />
        </svg>

        <svg
            class="pointer-events-none absolute bottom-0 left-0 h-10 w-full -scale-x-100 text-white/15"
            viewBox="0 0 1440 160"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <path
                fill="currentColor"
                d="M0 116C120 60 240 60 360 116s240 56 360 0 240-56 360 0 240 56 360 0v44H0v-44Z"
            />
        </svg>

    </div>
</x-filament-widgets::widget>
