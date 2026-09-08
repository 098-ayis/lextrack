<?php

namespace App\Http\Controllers;

use App\Services\MonthlyReportPdfService;
use App\Services\MonthlyReportService;
use App\Services\MonthlyReportWordService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MonthlyReportController extends Controller
{
    public function __invoke(Request $request, MonthlyReportService $reports): View|Response
    {
        $validated = $request->validate(['month' => ['required', 'date_format:Y-m'], 'format' => ['nullable', 'in:docx,pdf']]);

        $report = $reports->generate($validated['month']);
        $format = $validated['format'] ?? null;
        if (in_array($format, ['docx', 'pdf'], true)) {
            $service = $format === 'pdf' ? MonthlyReportPdfService::class : MonthlyReportWordService::class;
            $content = app($service)->build($report, $request->user()->name);

            return response($content, 200, [
                'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="monthly-report-'.$validated['month'].'.'.$format.'"',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return view('reports.monthly', $report + ['reportMonth' => $validated['month']]);
    }
}
