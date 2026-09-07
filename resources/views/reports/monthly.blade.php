<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monthly Accomplishment Report — {{ $month }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: #111; font: 11pt Georgia, serif; }
        .toolbar { padding: 20px; font: 14px system-ui; background: white; }
        .toolbar label { display: inline-block; margin: 8px 20px 8px 0; }
        button { padding: 10px 18px; cursor: pointer; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 20px auto; background: white; padding: 48mm 17mm 28mm; }
        .letterhead { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
        .content { position: relative; }
        .default-header { position: absolute; top: 12mm; left: 17mm; right: 17mm; border-bottom: 2px solid #111; padding-bottom: 6mm; display: flex; gap: 12px; align-items: center; }
        .default-header img { width: 20mm; }
        h1 { font-size: 15pt; text-align: center; margin: 0 0 8px; }
        .period { text-align: center; }
        .stats { display: flex; gap: 12px; margin: 20px 0; }
        .stats div { flex: 1; border: 1px solid #aaa; padding: 8px; font-size: 9pt; }
        .stats strong { display: block; font-size: 18pt; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; font-size: 9pt; }
        td, th { border: 1px solid #aaa; padding: 7px; text-align: left; overflow-wrap: anywhere; vertical-align: top; }
        .note { font-size: 9pt; line-height: 1.4; }
        .page-number { text-align: right; font-size: 9pt; margin-top: 14px; }
        body.custom-letterhead .default-header { display: none; }
        @page { size: A4; margin: 0; }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { margin: 0; break-after: page; }
            .sheet:last-child { break-after: auto; }
        }
        @media screen and (max-width: 800px) { .sheet { width: 100%; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong>Monthly accomplishment report · {{ $month }}</strong><br>
        <label>Official blank letterhead (PNG / JPG)
            <input id="letterhead" type="file" accept="image/png,image/jpeg">
        </label>
        <button type="button" id="print">Print / Save as PDF</button>
        <p>Choose a blank A4 letterhead without the “LETTERHEAD” placeholder. The image is used only in this preview and is not uploaded or saved. In the print dialog, select A4, 100% scale, and disable browser headers and footers.</p>
    </div>
    @php
        $pages = $activities->chunk(7);
        if ($pages->isEmpty()) { $pages = collect([collect()]); }
    @endphp
    @foreach ($pages as $entries)
        <section class="sheet">
            <img class="letterhead" alt="" hidden>
            <header class="default-header">
                <img src="{{ asset('images/bu-logo.png') }}" alt="Bicol University">
                <div>Republic of the Philippines<br><strong>BICOL UNIVERSITY</strong><br>LEGAL AFFAIRS OFFICE<br>Legazpi City</div>
            </header>
            <main class="content">
                <h1>MONTHLY ACCOMPLISHMENT REPORT</h1>
                <p class="period">{{ $month }}</p>
                @if ($loop->first)
                    <div class="stats">
                        <div><strong>{{ $received }}</strong>Documents received</div>
                        <div><strong>{{ $processed }}</strong>Documents processed</div>
                        <div><strong>{{ $completed }}</strong>Documents completed</div>
                        <div><strong>{{ $requests }}</strong>Requests processed</div>
                    </div>
                    <p class="note">Processed counts unique documents with recorded acceptance, update, outgoing, rejection, return, or completion actions during this month. Completed counts unique documents with a recorded completion action or status change. Requests count accepted or rejected requests by processing date. Counts can overlap; downloads and views are excluded. Missing historical logs are not inferred from current status.</p>
                @endif
                <table>
                    <thead><tr><th style="width: 17%">Date</th><th style="width: 27%">Document / type</th><th>Accomplishment</th></tr></thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td>{{ $entry->created_at->format('M d, Y') }}</td>
                                <td>{{ $entry->document?->lao_number ?? 'Unavailable document' }}<br>{{ $entry->document?->other_document_type ?: $entry->document?->type?->type_name }}</td>
                                <td><strong>{{ $entry->action_type }}</strong><br>{{ \Illuminate\Support\Str::limit($entry->action_details, 180) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No processing activities recorded for this month.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($loop->last)
                    <p style="margin-top: 24px">Prepared by: {{ auth()->user()->name }}</p>
                @endif
                <p class="page-number">Generated {{ now()->format('M d, Y H:i') }} · Page {{ $loop->iteration }} of {{ $pages->count() }}</p>
            </main>
        </section>
    @endforeach
    <script>
        let letterheadUrl;
        document.getElementById('letterhead').addEventListener('change', async function () {
            const file = this.files[0];
            if (file && !['image/png', 'image/jpeg'].includes(file.type)) {
                alert('Please choose a PNG or JPG image.');
                this.value = '';
                return;
            }
            if (letterheadUrl) URL.revokeObjectURL(letterheadUrl);
            letterheadUrl = file ? URL.createObjectURL(file) : null;
            document.body.classList.toggle('custom-letterhead', Boolean(file));
            document.querySelectorAll('.letterhead').forEach(img => {
                img.hidden = !file;
                if (file) img.src = letterheadUrl;
                else img.removeAttribute('src');
            });
        });
        document.getElementById('print').addEventListener('click', async () => {
            await Promise.all(Array.from(document.images).filter(img => !img.hidden).map(img => img.decode().catch(() => {})));
            window.print();
        });
    </script>
</body>
</html>
