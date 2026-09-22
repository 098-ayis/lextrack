@php
    $returnTo = request()->query('return_to');
    $returnParts = is_string($returnTo) ? parse_url($returnTo) : false;
    $returnHost = is_array($returnParts) ? ($returnParts['host'] ?? null) : null;
    $isSafeRelativePath = is_array($returnParts)
        && ! isset($returnParts['host'])
        && str_starts_with($returnParts['path'] ?? '', '/')
        && ! str_starts_with($returnParts['path'] ?? '', '//');
    $isSameOriginUrl = is_array($returnParts)
        && isset($returnHost)
        && strcasecmp($returnHost, request()->getHost()) === 0
        && (! isset($returnParts['scheme']) || strcasecmp($returnParts['scheme'], request()->getScheme()) === 0);
    $backUrl = $isSafeRelativePath || $isSameOriginUrl
        ? ($isSafeRelativePath
            ? $returnTo
            : ($returnParts['path'] ?? '/') . (isset($returnParts['query']) ? '?' . $returnParts['query'] : '') . (isset($returnParts['fragment']) ? '#' . $returnParts['fragment'] : ''))
        : \App\Filament\Pages\Document::getUrl(['section' => 'incoming']);
    $softCopyRequestPurpose = $document->documentRequests()
        ->where('copy_type', 'soft_copy')
        ->where('status', 'accepted')
        ->latest('date_of_request')
        ->value('purpose');
@endphp

<div class="fi-admin-document-context">
    <a
        href="{{ $backUrl }}"
        wire:navigate
        class="fi-admin-document-back"
        aria-label="Go back to the previous page"
        title="Back"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m0 0 7 7m-7-7 7-7" />
        </svg>
    </a>

    <div class="fi-admin-document-context-copy">
        <h1 title="{{ $document->particulars ?: $document->description ?: 'Untitled document' }}">
            {{ $document->particulars ?: $document->description ?: 'Untitled document' }}
        </h1>
        <p>{{ $softCopyRequestPurpose ?: ($document->lao_number ?: '—') }}</p>
    </div>
</div>
