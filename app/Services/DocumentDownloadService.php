<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Throwable;

class DocumentDownloadService
{
    public function preview(Document $document, string $sourcePath, ?string $fileName = null): BinaryFileResponse|Response
    {
        try {
            if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
                throw new RuntimeException('The document preview source file is not available.');
            }

            // DOCX files can be rendered in the browser with docx-preview. This
            // avoids requiring LibreOffice on the web server just to display a
            // document preview.
            if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) === 'docx') {
                return app(DocumentPreviewService::class)->preview($sourcePath);
            }

        $cacheDirectory = storage_path('app/private/document-previews');

        if (! is_dir($cacheDirectory) && ! mkdir($cacheDirectory, 0775, true) && ! is_dir($cacheDirectory)) {
            throw new RuntimeException('Unable to create the document preview cache directory.');
        }

        $sourceHash = hash_file('sha256', $sourcePath);

        if ($sourceHash === false) {
            throw new RuntimeException('Unable to read the document preview source file.');
        }

        // Include a version so layout changes invalidate previously generated
        // previews without requiring manual cleanup of the private cache.
        $cacheKey = hash('sha256', 'preview-qr-v2|'.$document->document_id.'|'.$sourceHash);
        $cachedPath = $cacheDirectory.'/'.$cacheKey.'.pdf';
        $lockPath = $cachedPath.'.lock';
        $lock = fopen($lockPath, 'c');

        if ($lock === false) {
            throw new RuntimeException('Unable to lock the document preview cache.');
        }

        try {
            flock($lock, LOCK_EX);

            if (! is_file($cachedPath) || filesize($cachedPath) === 0) {
                $this->generatePreview($document, $sourcePath, $cachedPath);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $previewName = pathinfo($fileName ?: $sourcePath, PATHINFO_FILENAME).'.pdf';

            return response()->file(
                $cachedPath,
                [
                    'Content-Type' => 'application/pdf',
                    'Cache-Control' => 'private, max-age=3600',
                ],
            )
                ->setContentDisposition('inline', $previewName);
        } catch (Throwable) {
            return app(DocumentPreviewService::class)->unavailable();
        }
    }

    private function generatePreview(Document $document, string $sourcePath, string $cachedPath): void
    {
        $temporaryDirectory = storage_path('app/private/temp-downloads');

        if (! is_dir($temporaryDirectory) && ! mkdir($temporaryDirectory, 0775, true) && ! is_dir($temporaryDirectory)) {
            throw new RuntimeException('Unable to create the temporary preview directory.');
        }

        $workingDirectory = $temporaryDirectory.'/'.bin2hex(random_bytes(16));
        File::makeDirectory($workingDirectory, 0775, true);
        $temporaryPath = $workingDirectory.'-with-qr.pdf';

        try {
            $pdfPath = $this->convertToPdf($sourcePath, $workingDirectory);
            $this->stampQrCode($pdfPath, $temporaryPath, $document->document_id);

            if (! rename($temporaryPath, $cachedPath)) {
                throw new RuntimeException('Unable to save the generated document preview.');
            }
        } catch (Throwable $exception) {
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }

            throw $exception;
        } finally {
            File::deleteDirectory($workingDirectory);
        }
    }


    public function download(Document $document, DocumentVersion $version): BinaryFileResponse
    {
        $filePath = (string) $version->file_path;
        $disk = $version->storageDisk();

        if ($filePath === '' || ! $disk->exists($filePath)) {
            throw new RuntimeException('Document file not found.');
        }

        $temporaryDirectory = storage_path('app/private/temp-downloads');

        File::ensureDirectoryExists($temporaryDirectory);

        $workingDirectory = $temporaryDirectory.'/'.bin2hex(random_bytes(16));
        File::ensureDirectoryExists($workingDirectory);

        $originalExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $extension = $originalExtension === 'docx' ? 'docx' : 'pdf';

        $sourcePath = $workingDirectory.'/source.'.$originalExtension;
        $temporaryPath = $workingDirectory.'-with-qr.'.$extension;

        try {
            // Read from the recorded disk: local or Backblaze B2.
            $sourceStream = $disk->readStream($filePath);

            if (! is_resource($sourceStream)) {
                throw new RuntimeException('Unable to read document source.');
            }

            try {
                $destination = fopen($sourcePath, 'wb');

                if ($destination === false) {
                    throw new RuntimeException('Unable to create temporary source file.');
                }

                try {
                    if (stream_copy_to_stream($sourceStream, $destination) === false) {
                        throw new RuntimeException('Unable to copy document source.');
                    }
                } finally {
                    fclose($destination);
                }
            } finally {
                fclose($sourceStream);
            }

            if ($extension === 'docx') {
                $statusUrl = URL::signedRoute(
                    'documents.public-status',
                    ['document' => $document->document_id]
                );

                app(DocumentWordQrService::class)->stamp(
                    $sourcePath,
                    $temporaryPath,
                    $this->qrImage($statusUrl),
                    $statusUrl
                );
            } else {
                $pdfPath = $this->convertToPdf($sourcePath, $workingDirectory);

                $this->stampQrCode(
                    $pdfPath,
                    $temporaryPath,
                    $document->document_id
                );
            }
        } catch (Throwable $exception) {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }

            throw $exception;
        } finally {
            File::deleteDirectory($workingDirectory);
        }

        $originalName = basename($version->original_filename ?: $filePath);
        $downloadName = pathinfo($originalName, PATHINFO_FILENAME);
        $downloadName = ($downloadName !== '' ? $downloadName : 'document').'.'.$extension;

        return response()->download(
            $temporaryPath,
            $downloadName,
            [
                'Content-Type' => $extension === 'docx'
                    ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    : 'application/pdf',
            ]
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
        $qrCode = $this->qrImage($statusUrl);

        $qrPath = tempnam(sys_get_temp_dir(), 'document-qr-');
        if ($qrPath === false) {
            throw new RuntimeException('Unable to create the QR image.');
        }

        $decryptedPath = $targetPath.'.decrypted.pdf';

        try {
            if (file_put_contents($qrPath, $qrCode) === false) {
                throw new RuntimeException('Unable to save the QR image.');
            }

            // Import PDF page content directly: text, fonts and graphics stay
            // at their original quality instead of becoming a flattened page
            // image. FPDI cannot read encrypted PDFs, so rewrite those PDFs
            // with Ghostscript first and then import the rewritten copy.
            $pdf = new \setasign\Fpdi\Fpdi;
            $pdf->SetAutoPageBreak(false);

            try {
                $pageCount = $pdf->setSourceFile($sourcePath);
            } catch (\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException $exception) {
                if ($exception->getCode() !== \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException::ENCRYPTED) {
                    throw $exception;
                }

                $this->rewriteEncryptedPdf($sourcePath, $decryptedPath);

                // Use a fresh FPDI instance because the failed parser has
                // already inspected the encrypted source file.
                $pdf = new \setasign\Fpdi\Fpdi;
                $pdf->SetAutoPageBreak(false);
                $pageCount = $pdf->setSourceFile($decryptedPath);
            }

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $template = $pdf->importPage($pageNumber, importExternalLinks: true);
                $page = $pdf->getTemplateSize($template);
                $pdf->AddPage($page['orientation'], [$page['width'], $page['height']]);
                $pdf->useTemplate($template);

                if ($pageNumber === 1) {
                    // Keep the tracking QR in the document's upper-right corner
                    // without changing the source page size or selectable text.
                    $size = min(20.0, min($page['width'], $page['height']) * 0.095);
                    // Keep the QR balanced inside the upper-right corner:
                    // 15 mm (1.5 cm) from the top and right page edges.
                    $margin = 15.0;
                    $pdf->Image($qrPath, $page['width'] - $size - $margin, $margin, $size, $size, 'PNG', $statusUrl);
                }
            }

            $pdf->Output('F', $targetPath);
        } finally {
            unlink($qrPath);

            if (file_exists($decryptedPath)) {
                unlink($decryptedPath);
            }
        }
    }

    private function rewriteEncryptedPdf(string $sourcePath, string $targetPath): void
    {
        $process = new Process([
            'gs',
            '-q',
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-sOutputFile='.$targetPath,
            $sourcePath,
        ]);
        $process->setTimeout(120);

        try {
            $process->mustRun();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The PDF is encrypted and could not be prepared for QR tracking. '
                .'Please save or upload an unprotected copy of the document.',
                previous: $exception,
            );
        }

        if (! is_file($targetPath) || filesize($targetPath) === 0) {
            throw new RuntimeException(
                'The PDF is encrypted and could not be prepared for QR tracking. '
                .'Please save or upload an unprotected copy of the document.'
            );
        }
    }

    private function qrImage(string $statusUrl): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'scale' => 10,
        ])))->render($statusUrl);
    }
}
