<div class="fi-admin-document-context">
    <button
        type="button"
        class="fi-admin-document-back"
        aria-label="Go back to the previous page"
        title="Back"
        onclick="window.history.back()"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m0 0 7 7m-7-7 7-7" />
        </svg>
    </button>

    <div class="fi-admin-document-context-copy">
        <h1 title="{{ $document->particulars ?: $document->description ?: 'Untitled document' }}">
            {{ $document->particulars ?: $document->description ?: 'Untitled document' }}
        </h1>
        <p>{{ $document->lao_number ?: '—' }}</p>
    </div>
</div>
