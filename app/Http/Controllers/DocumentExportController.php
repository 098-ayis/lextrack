<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentSpreadsheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $section = $this->normalizeSection($request->query('section'));
        $search = $this->normalizeString($request->query('search'));
        $typeFilter = $this->normalizeString($request->query('type'));
        $dateFilter = $this->normalizeDate($request->query('date'));

        try {
            $status = match ($section) {
                'pending' => 'pending',
                'incoming' => 'in_progress',
                'outgoing' => 'outgoing',
                'completed' => 'completed',
                'rejected' => 'rejected',
                'archived' => 'archived',
            };

            $documents = Document::query()
                ->with(['user', 'officeUnit'])
                ->where('status', $status)
                ->when($search !== '', function ($query) use ($search): void {
                    $likeSearch = "%{$search}%";

                    $query->where(function ($query) use ($likeSearch): void {
                        $query
                            ->where('lao_number', 'like', $likeSearch)
                            ->orWhereHas('officeUnit', fn ($officeQuery) => $officeQuery->where('name', 'like', $likeSearch))
                            ->orWhere('particulars', 'like', $likeSearch);
                    });
                })
                ->when($typeFilter !== '', function ($query) use ($typeFilter): void {
                    $query->where('document_type', $typeFilter);
                })
                ->when($dateFilter !== '', function ($query) use ($dateFilter): void {
                    $query->whereDate('created_at', $dateFilter);
                })
                ->latest('created_at')
                ->get();

            $sectionLabel = ucfirst($section);
            $xlsx = $this->buildSpreadsheet($documents, $sectionLabel);
        } catch (Throwable $exception) {
            Log::error('Document export failed.', [
                'section' => $section,
                'search' => $search,
                'type' => $typeFilter,
                'date' => $dateFilter,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'The document export could not be generated. Please try again.',
            ], 500);
        }

        return response()->streamDownload(
            static function () use ($xlsx): void {
                echo $xlsx;
            },
            'documents-'.now()->format('Y-m-d').'.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ],
        );
    }

    private function buildSpreadsheet(Collection $documents, string $sectionLabel): string
    {
        $rows = collect();
        foreach ($documents as $index => $document) {
            $type = $document->document_type ?? 'Unknown';
            $status = ucwords(str_replace('_', ' ', (string) $document->status));
            $action = $document->action_type ?? '—';

            $rows->push([
                $index + 1,
                $document->lao_number,
                $document->officeUnit?->name,
                $document->particulars,
                $type,
                $document->user?->name ?? '—',
                $this->formatDate($document->created_at) ?? '—',
                $action,
                $status,
                $this->formatDate($document->outgoing_date) ?? '—',
                $document->sent_to ?? '—',
                $this->formatDate($document->sent_date) ?? '—',
                $this->formatDate($document->updated_at) ?? '—',
            ]);
        }

        return app(DocumentSpreadsheetService::class)->build($rows, $sectionLabel);
    }

    private function normalizeSection(mixed $section): string
    {
        return is_string($section) && in_array($section, [
            'pending',
            'incoming',
            'outgoing',
            'completed',
            'rejected',
            'archived',
        ], true) ? $section : 'incoming';
    }

    private function normalizeString(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function normalizeDate(mixed $value): string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            return '';
        }

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function formatDate(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('F d, Y') : null;
    }
}
