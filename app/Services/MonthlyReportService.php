<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentRequest;
use Carbon\CarbonImmutable;

class MonthlyReportService
{
    public const PROCESSING_ACTIONS = [
        'Document accepted', 'Document updated', 'Document moved to outgoing',
        'Document rejected', 'Document returned', 'Document completed',
    ];

    public function generate(string $month): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m', $month);
        $end = $start->addMonth();
        $activities = ActivityLog::query()
            ->with('document.type')
            ->where('created_at', '>=', $start)->where('created_at', '<', $end)
            ->whereIn('action_type', self::PROCESSING_ACTIONS)
            ->orderBy('created_at')->orderBy('log_id')->get();

        return [
            'month' => $start->format('F Y'),
            'activities' => $activities,
            'received' => Document::where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            'processed' => $activities->pluck('document_id')->unique()->count(),
            'completed' => $activities->filter(function ($log) {
                if ($log->action_type === 'Document completed') {
                    return true;
                }

                $old = json_decode($log->old_value ?? '', true);
                $new = json_decode($log->new_value ?? '', true);

                return $log->action_type === 'Document updated'
                    && ($new['status'] ?? null) === 'completed'
                    && ($old['status'] ?? null) !== 'completed';
            })->pluck('document_id')->unique()->count(),
            'requests' => DocumentRequest::where('date_processed', '>=', $start->toDateString())
                ->where('date_processed', '<', $end->toDateString())
                ->whereIn('status', ['accepted', 'rejected'])->count(),
        ];
    }
}
