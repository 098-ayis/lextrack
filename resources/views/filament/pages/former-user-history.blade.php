<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-warning-100 px-2.5 py-1 text-xs font-semibold text-warning-800 dark:bg-warning-950 dark:text-warning-200">
                        {{ $user->historical_status_label }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Deleted {{ $user->deleted_at?->format('M d, Y g:i A') ?? 'Unknown date' }}
                    </span>
                </div>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">{{ $user->historical_name }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Original role: {{ $user->roles->pluck('name')->join(', ') ?: 'Unassigned' }}
                    · Account status before deletion: {{ filled($user->status) ? ucfirst($user->status) : 'Unknown' }}
                </p>
            </div>

            <a
                href="{{ \App\Filament\Pages\FormerUsers::getUrl() }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                Back to Former Users
            </a>
        </div>

        <nav class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Former user history">
            @foreach ([
                ['id' => 'documents', 'label' => 'Documents', 'count' => $documents->count()],
                ['id' => 'requests', 'label' => 'Requests', 'count' => $requests->count()],
                ['id' => 'messages', 'label' => 'Messages', 'count' => $messages->count()],
                ['id' => 'notes', 'label' => 'Notes', 'count' => $notes->count()],
                ['id' => 'audit-logs', 'label' => 'Audit Logs', 'count' => $auditLogs->count()],
            ] as $item)
                <a href="#{{ $item['id'] }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-400 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                    <span class="block text-sm font-medium text-gray-600 dark:text-gray-300">{{ $item['label'] }}</span>
                    <span class="mt-1 block text-2xl font-semibold text-gray-950 dark:text-white">{{ $item['count'] }}</span>
                    <span class="mt-1 block text-xs text-primary-600 dark:text-primary-400">View history →</span>
                </a>
            @endforeach
        </nav>

        <section id="documents" class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-950 dark:text-white">Documents submitted</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($documents as $document)
                    <a href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->getPublicRouteKey()]) }}" class="block px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-primary-600 dark:text-primary-400">{{ $document->document_name ?: $document->particulars ?: $document->lao_number ?: 'Untitled document' }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $document->created_at?->format('M d, Y g:i A') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $document->lao_number ?: 'No LAO number' }} · {{ ucfirst(str_replace('_', ' ', (string) $document->status)) }}</p>
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">No documents found.</p>
                @endforelse
            </div>
        </section>

        <section id="requests" class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-950 dark:text-white">Document requests</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($requests as $request)
                    <a href="{{ \App\Filament\Pages\DocumentRequests::getUrl(['section' => $request->status]) }}" class="block px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-primary-600 dark:text-primary-400">Request #{{ $request->getKey() }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $request->date_of_request?->format('M d, Y') }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $request->purpose ?: 'No purpose recorded' }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ucfirst((string) $request->status) }}{{ $request->document?->lao_number ? ' · '.$request->document->lao_number : '' }}</p>
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">No document requests found.</p>
                @endforelse
            </div>
        </section>

        <section id="messages" class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-950 dark:text-white">Messages sent</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Messages remain attributed to {{ $user->historical_name }} after deletion.</p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($messages as $message)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-gray-800 dark:text-gray-100">Conversation #{{ $message->conversation_id }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $message->created_at?->format('M d, Y g:i A') }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-200">{{ \Illuminate\Support\Str::limit((string) $message->body, 240) }}</p>
                        @if ($message->conversation?->document)
                            <a href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $message->conversation->document->getPublicRouteKey()]) }}" class="mt-2 inline-block text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Open related document →</a>
                        @elseif ($message->conversation?->documentRequest)
                            <a href="#requests" class="mt-2 inline-block text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Open related request history →</a>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">No messages found.</p>
                @endforelse
            </div>
        </section>

        <section id="notes" class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-950 dark:text-white">Notes authored</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($notes as $note)
                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700 dark:text-gray-200">{{ \Illuminate\Support\Str::limit((string) $note->note, 240) }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $note->created_at?->format('M d, Y g:i A') }}</span>
                            @if ($note->document)
                                <span>·</span>
                                <a href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $note->document->getPublicRouteKey()]) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Open related document →</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">No notes found.</p>
                @endforelse
            </div>
        </section>

        <section id="audit-logs" class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-950 dark:text-white">Audit logs</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($auditLogs as $log)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $log->action_type }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at?->format('M d, Y g:i A') }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $log->action_details }}</p>
                        @if ($log->document)
                            <a href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $log->document->getPublicRouteKey()]) }}" class="mt-2 inline-block text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Open related document →</a>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">No audit logs found.</p>
                @endforelse
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-950 dark:text-white">Versions uploaded</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($versions as $version)
                        <a href="{{ $version->document ? \App\Filament\Pages\ViewDocument::getUrl(['document' => $version->document->getPublicRouteKey()]) : '#' }}" class="block px-5 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                            <span class="font-medium text-primary-600 dark:text-primary-400">Version {{ $version->version_number }}</span>
                            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $version->created_at?->format('M d, Y g:i A') }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">No versions found.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-950 dark:text-white">Conversations</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($conversations as $conversation)
                        <div class="px-5 py-3 text-sm">
                            <span class="font-medium text-gray-800 dark:text-gray-100">Conversation #{{ $conversation->getKey() }}</span>
                            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                {{ $conversation->creator?->historical_display_name ?: 'Unknown creator' }}
                                · {{ $conversation->participants->count() }} participant(s)
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">No conversations found.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-950 dark:text-white">Calendar history</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($calendarEvents as $event)
                        <div class="px-5 py-3 text-sm">
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $event->event }}</span>
                            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $event->date?->format('M d, Y') }}{{ $event->time ? ' · '.$event->time->format('g:i A') : '' }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">No calendar history found.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
