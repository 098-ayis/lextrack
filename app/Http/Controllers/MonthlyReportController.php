<?php

namespace App\Http\Controllers;

use App\Services\MonthlyReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyReportController extends Controller
{
    public function __invoke(Request $request, MonthlyReportService $reports): View
    {
        $validated = $request->validate(['month' => ['required', 'date_format:Y-m']]);

        return view('reports.monthly', $reports->generate($validated['month']));
    }
}
