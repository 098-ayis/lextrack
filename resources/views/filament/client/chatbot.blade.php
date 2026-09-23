@if (request()->routeIs('filament.client.pages.dashboard'))
    @php
        $chatbotSessionKey = session('chatbot.browser_session_key');

        if (! is_string($chatbotSessionKey) || $chatbotSessionKey === '') {
            $chatbotSessionKey = (string) \Illuminate\Support\Str::uuid();
            session(['chatbot.browser_session_key' => $chatbotSessionKey]);
        }
    @endphp

    <div id="client-chatbot" data-session-key="{{ $chatbotSessionKey }}"></div>

    @vite('resources/js/client-chatbot.js')
@endif
