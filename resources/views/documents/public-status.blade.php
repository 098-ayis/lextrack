<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Status</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            color: #0f172a;
        }

        main {
            width: min(100% - 2rem, 42rem);
            margin: 0 auto;
            padding: 3rem 0;
        }

        .card {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        .heading {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .subtitle {
            margin: .5rem 0 0;
            color: #64748b;
            font-size: .9rem;
        }

        .status {
            display: inline-flex;
            margin-top: 1rem;
            padding: .4rem .75rem;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: .8rem;
            font-weight: 700;
        }

        dl {
            margin: 0;
            padding: .5rem 1.5rem 1.5rem;
        }

        .row {
            display: grid;
            grid-template-columns: 11rem 1fr;
            gap: 1rem;
            padding: .85rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .row:last-child {
            border-bottom: 0;
        }

        dt {
            color: #64748b;
            font-size: .85rem;
        }

        dd {
            margin: 0;
            color: #1e293b;
            font-size: .9rem;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .notice {
            margin: 1.5rem;
            padding: .9rem 1rem;
            border-radius: .65rem;
            background: #f8fafc;
            color: #64748b;
            font-size: .8rem;
        }

        @media (max-width: 30rem) {
            .row {
                grid-template-columns: 1fr;
                gap: .25rem;
            }
        }
    </style>
</head>
<body>
    @php
        $statusLabels = [
            'pending' => 'Pending',
            'in_progress' => 'Incoming / In Progress',
            'outgoing' => 'Outgoing',
            'completed' => 'Archived / Completed',
            'rejected' => 'Rejected',
        ];

        $statusColors = [
            'pending' => ['background: #fef3c7', 'color: #b45309'],
            'in_progress' => ['background: #dbeafe', 'color: #1d4ed8'],
            'outgoing' => ['background: #e0e7ff', 'color: #4338ca'],
            'completed' => ['background: #dcfce7', 'color: #15803d'],
            'rejected' => ['background: #fee2e2', 'color: #b91c1c'],
        ];

        $statusStyle = implode('; ', $statusColors[$document->status] ?? ['background: #f1f5f9', 'color: #475569']);
        $documentType = $document->type?->type_name
            ?? $document->other_document_type
            ?? 'Not specified';
    @endphp

    <main>
        <section class="card">
            <div class="heading">
                <h1>Document Details</h1>
                <p class="subtitle">This information was opened from a document QR code.</p>
                <span class="status" style="{{ $statusStyle }}">
                    {{ $statusLabels[$document->status] ?? ucfirst(str_replace('_', ' ', $document->status ?? 'Unknown')) }}
                </span>
            </div>

            <dl>
                <div class="row">
                    <dt>LAO Number</dt>
                    <dd>{{ $document->lao_number ?: 'Not assigned' }}</dd>
                </div>
                <div class="row">
                    <dt>Document Type</dt>
                    <dd>{{ $documentType }}</dd>
                </div>
                <div class="row">
                    <dt>Uploaded By</dt>
                    <dd>{{ $document->user?->name ?? 'Unknown user' }}</dd>
                </div>
                <div class="row">
                    <dt>Office / Unit</dt>
                    <dd>{{ $document->office_unit ?: 'Not specified' }}</dd>
                </div>
                <div class="row">
                    <dt>Particulars</dt>
                    <dd>{{ $document->particulars ?: 'Not specified' }}</dd>
                </div>
                <div class="row">
                    <dt>Action Taken</dt>
                    <dd>{{ $document->action_taken ?: ($document->actionType?->action_name ?? 'Not specified') }}</dd>
                </div>
                <div class="row">
                    <dt>Deadline</dt>
                    <dd>{{ $document->deadline?->format('F d, Y') ?? 'No deadline set' }}</dd>
                </div>
                <div class="row">
                    <dt>Uploaded Date</dt>
                    <dd>{{ $document->created_at?->format('F d, Y h:i A') ?? 'Not available' }}</dd>
                </div>
                <div class="row">
                    <dt>Latest Updated</dt>
                    <dd>{{ $document->updated_at?->format('F d, Y h:i A') ?? 'Not available' }}</dd>
                </div>
                @if ($document->sent_to || $document->sent_date)
                    <div class="row">
                        <dt>Sent</dt>
                        <dd>
                            {{ $document->sent_to ?: 'Destination not specified' }}
                            @if ($document->sent_date)
                                <br>{{ \Carbon\Carbon::parse($document->sent_date)->format('F d, Y') }}
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($document->returned_from || $document->date_returned)
                    <div class="row">
                        <dt>Returned</dt>
                        <dd>
                            {{ $document->returned_from ?: 'Source not specified' }}
                            @if ($document->date_returned)
                                <br>{{ \Carbon\Carbon::parse($document->date_returned)->format('F d, Y') }}
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($document->status === 'rejected' && $document->rejection_reason)
                    <div class="row">
                        <dt>Rejection Reason</dt>
                        <dd>{{ $document->rejection_reason }}</dd>
                    </div>
                @endif
            </dl>

            <p class="notice">
                This QR page shows document status and details only. Uploaded files remain protected.
            </p>
        </section>
    </main>
</body>
</html>
