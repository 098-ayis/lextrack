<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Throwable;

class DocumentDownloadService
{
    public function download(Document $document, DocumentVersion $version): BinaryFileResponse
    {
        $sourcePath = $version->storageDisk()->path((string) $version->file_path);
        $temporaryDirectory = storage_path('app/private/temp-downloads');

        if (! is_dir($temporaryDirectory) && ! mkdir($temporaryDirectory, 0775, true) && ! is_dir($temporaryDirectory)) {
            throw new RuntimeException('Unable to create the temporary download directory.');
        }

        $workingDirectory = $temporaryDirectory.'/'.bin2hex(random_bytes(16));
        File::makeDirectory($workingDirectory, 0775, true);
        $temporaryPath = $workingDirectory.'-with-qr.pdf';

        try {
            $pdfPath = $this->convertToPdf($sourcePath, $workingDirectory);
            $this->stampQrCode($pdfPath, $temporaryPath, $document->document_id);
        } catch (Throwable $exception) {
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }

            throw $exception;
        } finally {
            File::deleteDirectory($workingDirectory);
        }

        return response()->download(
            $temporaryPath,
            pathinfo((string) $version->file_path, PATHINFO_FILENAME).'-with-qr.pdf'
        )->deleteFileAfterSend(true);
    }

    private function convertToPdf(string $sourcePath, string $temporaryDirectory): string
    {
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return $sourcePath;
        }

        if (! in_array($extension, ['doc', 'docx'], true)) {
            throw new RuntimeException('QR code attachment is not supported for this file type.');
        }

        // A writable, per-download profile avoids headless startup failures and
        // prevents simultaneous LibreOffice processes sharing a profile.
        $profile = 'file://'.$temporaryDirectory.'/libreoffice-profile';
        $process = new Process([
            'libreoffice',
            '-env:UserInstallation='.$profile,
            '--headless',
            '--convert-to', 'pdf:writer_pdf_Export',
            '--outdir', $temporaryDirectory,
            $sourcePath,
        ]);
        $process->setTimeout(120);
        $process->run();

        $pdfPath = $temporaryDirectory.'/'.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf';

        if (! $process->isSuccessful() || ! is_file($pdfPath) || filesize($pdfPath) === 0) {
            throw new RuntimeException('Unable to convert the document to PDF: '.trim($process->getErrorOutput().' '.$process->getOutput()));
        }

        return $pdfPath;
    }

    private function stampQrCode(string $sourcePath, string $targetPath, int $documentId): void
    {
        $statusUrl = URL::signedRoute('documents.public-status', ['document' => $documentId]);
        $qrCode = (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'scale' => 10,
        ])))->render($statusUrl);

        // Import PDF page content directly: text, fonts and graphics stay at
        // their original quality instead of becoming a flattened page image.
        $pdf = new Fpdi;
        $pdf->SetAutoPageBreak(false);
        $pageCount = $pdf->setSourceFile($sourcePath);
        $qrPath = tempnam(sys_get_temp_dir(), 'document-qr-');
        if ($qrPath === false) {
            throw new RuntimeException('Unable to create the QR image.');
        }

        try {
            if (file_put_contents($qrPath, $qrCode) === false) {
                throw new RuntimeException('Unable to save the QR image.');
            }

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $template = $pdf->importPage($pageNumber, importExternalLinks: true);
                $page = $pdf->getTemplateSize($template);
                $pdf->AddPage($page['orientation'], [$page['width'], $page['height']]);
                $pdf->useTemplate($template);

                if ($pageNumber === 1) {
                    // Fit within the top margin of a standard document header.
                    $size = min(20.0, min($page['width'], $page['height']) * 0.095);
                    $pdf->Image($qrPath, $page['width'] - $size - 5, 3, $size, $size, 'PNG', $statusUrl);
                }
            }

            $pdf->Output('F', $targetPath);
        } finally {
            unlink($qrPath);
        }
    }
}
