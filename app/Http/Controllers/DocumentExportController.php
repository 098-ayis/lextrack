<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentExportController
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
                'archive' => 'archived',
                'rejected' => 'rejected',
            };

            $documents = Document::query()
                ->with(['user', 'type', 'actionType'])
                ->where('status', $status)
                ->when($search !== '', function ($query) use ($search): void {
                    $likeSearch = "%{$search}%";

                    $query->where(function ($query) use ($likeSearch): void {
                        $query
                            ->where('lao_number', 'like', $likeSearch)
                            ->orWhere('office_unit', 'like', $likeSearch)
                            ->orWhere('particulars', 'like', $likeSearch);
                    });
                })
                ->when($typeFilter !== '', function ($query) use ($typeFilter): void {
                    $query->where('type_id', $typeFilter);
                })
                ->when($dateFilter !== '', function ($query) use ($dateFilter): void {
                    $query->whereDate('created_at', $dateFilter);
                })
                ->latest('created_at')
                ->get();

            $sectionLabel = $section === 'archive' ? 'Archive' : ucfirst($section);
            $csv = $this->buildCsv($documents, $sectionLabel);
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
            static function () use ($csv): void {
                echo $csv;
            },
            'documents-' . now()->format('Y-m-d') . '.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ],
        );
    }

    private function buildCsv(Collection $documents, string $sectionLabel): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the CSV output stream.');
        }

        try {
            // UTF-8 BOM helps spreadsheet applications display the CSV correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['BICOL UNIVERSITY']);
            fputcsv($handle, ['LEGAL AFFAIRS OFFICE']);
            fputcsv($handle, ['BU LOGO', 'bu-logo.png']);
            fputcsv($handle, ['DOCUMENTS REPORT', $sectionLabel]);
            fputcsv($handle, ['Generated', now()->format('F d, Y h:i A')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No.',
                'LAO No.',
                'Office / Unit',
                'Particulars',
                'Document Type',
                'Uploaded By',
                'Upload Date',
                'Action Taken',
                'Status',
                'Outgoing Date',
                'Sent To',
                'Sent Date',
                'Latest Updated',
            ]);

            foreach ($documents as $index => $document) {
                $type = $document->type?->type_name ?? 'Unknown';
                $status = ucwords(str_replace('_', ' ', (string) $document->status));
                $action = $document->actionType?->action_name ?? $document->action_taken ?? '—';

                fputcsv($handle, [
                    $index + 1,
                    $document->lao_number,
                    $document->office_unit,
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

            rewind($handle);
            $csv = stream_get_contents($handle);

            if ($csv === false) {
                throw new RuntimeException('Unable to read the generated CSV.');
            }

            return $csv;
        } finally {
            fclose($handle);
        }
    }

    private function normalizeSection(mixed $section): string
    {
        return is_string($section) && in_array($section, [
            'pending',
            'incoming',
            'outgoing',
            'archive',
            'rejected',
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
