@props(['document', 'staffView' => false])

@if ($document && $document->lao_number && in_array($document->status, ['in_progress', 'outgoing', 'completed', 'archived'], true))
    @php
        $qrUrl = \Illuminate\Support\Facades\URL::signedRoute('documents.qr', ['document' => $document->document_id]);
    @endphp
    <section aria-label="Document QR code" style="margin: 18px 0; {{ $staffView ? 'margin-left: auto;' : 'margin-right: auto;' }} padding: 18px; max-width: 340px; border: 1px solid #dbe3ed; border-radius: 16px; background: white; color: #172554;">
        <p style="margin: 0 0 8px; font-size: 12px; font-weight: 600;">Legal Affairs Office</p>
        <strong>Document QR code</strong>
        <p style="margin: 6px 0; font-size: 14px;">{{ $document->lao_number }}</p>
        <a href="{{ $qrUrl }}" target="_blank" rel="noopener" aria-label="Open document QR code">
            <img src="{{ $qrUrl }}" alt="QR code for {{ $document->lao_number }}" width="200" height="200" style="display: block; width: 200px; height: 200px; max-width: 100%; background: white;">
        </a>
        <p style="margin: 10px 0; font-size: 13px;">Scan this QR code to view your document status.</p>
        <a href="{{ $qrUrl }}" download="document-{{ $document->document_id }}-qr.png" style="color: #4f46e5; font-weight: 600; font-size: 14px;">Download QR code</a>
    </section>
@endif
