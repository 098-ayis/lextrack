<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monthly Accomplishment Report — {{ $month }}</title>
    <style>
        @font-face { font-family: 'Report Serif'; src: url("{{ asset('fonts/reports/LiberationSerif-Regular.ttf') }}") format('truetype'); font-weight: 400; font-style: normal; }
        @font-face { font-family: 'Report Serif'; src: url("{{ asset('fonts/reports/LiberationSerif-Bold.ttf') }}") format('truetype'); font-weight: 700; font-style: normal; }
        @font-face { font-family: 'Report Serif'; src: url("{{ asset('fonts/reports/LiberationSerif-Italic.ttf') }}") format('truetype'); font-weight: 400; font-style: italic; }
        @font-face { font-family: 'Report Serif'; src: url("{{ asset('fonts/reports/LiberationSerif-BoldItalic.ttf') }}") format('truetype'); font-weight: 700; font-style: italic; }

        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: #111; font: 11pt 'Report Serif', serif; }
        .toolbar { padding: 20px; font: 14px system-ui; background: white; }
        .toolbar label { display: inline-block; margin: 8px 20px 8px 0; }
        button { padding: 10px 18px; cursor: pointer; }
        .report-action { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 44px; padding: 0 18px; border: 1px solid #d8d2eb; border-radius: 9px; background: white; color: #5633d4; font: 600 14px system-ui; box-shadow: 0 1px 2px #1018280d; transition: background .15s, border-color .15s, box-shadow .15s; }
        .report-action:hover { background: #f5f3ff; border-color: #a78bfa; }
        .report-action-primary { background: #5633ff; border-color: #5633ff; color: white; }
        .report-action-primary:hover { background: #4725df; border-color: #4725df; }
        .report-action:focus-visible,.save-options a:focus-visible,.save-options button:focus-visible { outline: 3px solid #c4b5fd; outline-offset: 3px; }
        .report-action svg { width: 18px; height: 18px; flex-shrink: 0; }
        .save-dropdown { position: relative; }
        .save-options { position: absolute; top: 100%; left: 0; z-index: 10; min-width: 180px; padding: 6px; background: white; border: 1px solid #e5e1f0; border-radius: 10px; box-shadow: 0 8px 24px #25134d1a; }
        .save-options[hidden] { display: none; }
        .save-options button,.save-options a { display: block; width: 100%; padding: 10px 12px; border: 0; background: white; color: #111; text-align: left; text-decoration: none; font: inherit; border-radius: 4px; }
        .save-options button:hover,.save-options a:hover,.save-options button:focus-visible,.save-options a:focus-visible { background: #f3e8ff; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 20px auto; background: white; padding: 58mm 12mm 35mm; }
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
        .default-header { top: 7mm; left: 6mm; right: 6mm; padding-bottom: 3mm; gap: 3mm; align-items: flex-start; font: 8pt Arial, sans-serif; }
        .default-header .bu-mark { width: 29mm; height: 35mm; object-fit: contain; }
        .university { color: #183963; font: bold 17pt 'Report Serif', serif; }
        .office-name { font-size: 11pt; }
        .contact { margin-top: 2mm; font-size: 6.5pt; }
        .partner-marks { margin-left: auto; display: flex; gap: 5mm; align-items: center; padding-top: 5mm; }
        .partner-marks img { width: 23mm; height: 23mm; object-fit: contain; }
        .office-script { position: absolute; top: 100%; left: 3mm; margin-top: 2mm; color: #183963; font: italic 14pt 'Report Serif', serif; }
        .official-footer { position: absolute; bottom: 8mm; left: 6mm; right: 6mm; display: flex; gap: 3mm; align-items: flex-start; }
        .official-footer img { width: 14mm; height: 14mm; }
        .motto { flex: 1; border-top: 3px solid #ed7d31; padding-top: 2mm; text-align: center; font: italic 9pt 'Report Serif', serif; }
        .sdg-box { width: 32mm; min-height: 15mm; border: 3px double #0099ff; padding: 2mm; color: #007ac2; font: 6pt Arial, sans-serif; }
        .sdg-box strong { display: block; margin-top: 3mm; font-size: 9pt; }
        tr { break-inside: avoid; }
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
        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:16px;align-items:center">
            <button type="button" id="print" class="report-action">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6zM18 12h.01"/></svg>
                Print
            </button>
            <div class="save-dropdown" id="save-dropdown">
                <button type="button" id="save-toggle" class="report-action report-action-primary" aria-expanded="false" aria-controls="save-options">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m-5-5 5 5 5-5M4 16v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></svg>
                    Save
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <div class="save-options" id="save-options" hidden>
                    <a href="{{ route('admin.reports.monthly', ['month' => $reportMonth, 'format' => 'pdf']) }}">PDF (.pdf)</a>
                    <a href="{{ route('admin.reports.monthly', ['month' => $reportMonth, 'format' => 'docx']) }}">Word (.docx)</a>
                </div>
            </div>
        </div>
        <p>Uses the official client template. Save downloads the selected PDF or Word file. When printing, use A4, 100% scale, and disable browser headers and footers.</p>
    </div>
    @php
        $pages = $activities->chunk(5);
        if ($pages->isEmpty()) { $pages = collect([collect()]); }
    @endphp
    @foreach ($pages as $entries)
        <section class="sheet">
            <header class="default-header">
                <img class="bu-mark" src="{{ asset('images/reports/bu-certified.png') }}" alt="Bicol University — ISO 9001:2015">
                <div>REPUBLIC OF THE PHILIPPINES<br><strong class="university">BICOL UNIVERSITY</strong><br><strong class="office-name">LEGAL AFFAIRS OFFICE</strong><br>Legazpi City<br>Email: op@bicol-u.edu.ph
                    <div class="contact"><strong>MANILA OFFICE:</strong><br>No. 4 Lopez St., M. H. del Pilar, Roosevelt Ave., Quezon City<br>Telefax: (02) 921-1586</div>
                </div>
                <div class="partner-marks">
                    <img src="{{ asset('images/reports/sdg.png') }}" alt="Sustainable Development Goals">
                    <img src="{{ asset('images/reports/bagong-pilipinas.png') }}" alt="Bagong Pilipinas">
                </div>
                <div class="office-script">Legal Affairs Office</div>
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
                                <td>{{ $entry->document?->lao_number ?? 'Unavailable document' }}<br>{{ $entry->document?->document_type }}</td>
                                <td><strong>{{ $entry->action_type }}</strong><br>{{ \Illuminate\Support\Str::limit($entry->action_details, 180) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No processing activities recorded for this month.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($loop->last)
                    <p style="margin-top: 24px">Prepared by: {{ $preparedBy ?? auth()->user()->name }}</p>
                @endif
                <p class="page-number">Generated {{ now()->format('M d, Y H:i') }} · Page {{ $loop->iteration }} of {{ $pages->count() }}</p>
            </main>
            <footer class="official-footer">
                <img src="{{ asset('images/reports/qr.png') }}" alt="Bicol University QR code">
                <div class="motto">A University for Humanity characterized by productive scholarship, transformative leadership, collaborative service and distinctive character for sustainable societies.</div>
                <div class="sdg-box">This communication is aligned to<strong>SDG No. ______</strong></div>
            </footer>
        </section>
    @endforeach
    <script>
        const printReport = async () => {
            await Promise.all(Array.from(document.images).filter(img => !img.hidden).map(img => img.decode().catch(() => {})));
            window.print();
        };
        document.getElementById('print').addEventListener('click', printReport);
        const dropdown = document.getElementById('save-dropdown');
        const toggle = document.getElementById('save-toggle');
        const options = document.getElementById('save-options');
        const setOpen = open => {
            options.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
        };
        dropdown.addEventListener('mouseenter', () => setOpen(true));
        dropdown.addEventListener('mouseleave', () => {
            if (!dropdown.contains(document.activeElement)) setOpen(false);
        });
        toggle.addEventListener('click', () => setOpen(true));
        dropdown.addEventListener('focusout', event => {
            if (!dropdown.contains(event.relatedTarget)) setOpen(false);
        });
        dropdown.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                setOpen(false);
                toggle.focus();
            }
            if (event.key === 'ArrowDown' && event.target === toggle) {
                event.preventDefault();
                setOpen(true);
                options.querySelector('a, button').focus();
            }
        });
        options.addEventListener('click', () => setOpen(false));
        document.addEventListener('click', event => {
            if (!dropdown.contains(event.target)) setOpen(false);
        });
    </script>
</body>
</html>
