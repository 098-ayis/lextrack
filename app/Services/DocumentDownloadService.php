<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

        $temporaryPath = $temporaryDirectory . '/' . uniqid('document-', true) . '.pdf';

        try {
            $pdfPath = $this->convertToPdf($sourcePath, $temporaryDirectory);
            $this->appendQrPage($pdfPath, $temporaryPath, $document->document_id);

            if ($pdfPath !== $sourcePath && file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        } catch (Throwable $exception) {
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }

            throw $exception;
        }

        return response()->download(
            $temporaryPath,
            pathinfo((string) $version->file_path, PATHINFO_FILENAME) . '-with-qr.pdf'
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

        $output = [];
        $exitCode = 0;
        $command = sprintf(
            'libreoffice --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($temporaryDirectory),
            escapeshellarg($sourcePath)
        );

        exec($command, $output, $exitCode);

        $pdfPath = $temporaryDirectory . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.pdf';

        if ($exitCode !== 0 || ! file_exists($pdfPath)) {
            throw new RuntimeException('Unable to convert the document to PDF.');
        }

        return $pdfPath;
    }

    private function appendQrPage(string $sourcePath, string $targetPath, int $documentId): void
    {
        $statusUrl = URL::signedRoute('documents.public-status', ['document' => $documentId]);
        $qrCode = (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'scale' => 10,
        ])))->render($statusUrl);

        $pages = new \Imagick();
        $pages->readImage($sourcePath);

        $qrPage = new \Imagick();
        $qrPage->newImage(1200, 1400, new \ImagickPixel('white'));
        $qrPage->setImageFormat('png');

        $qrImage = new \Imagick();
        $qrImage->readImageBlob($qrCode);
        $qrImage->setImageFormat('png');
        $qrImage->resizeImage(900, 900, \Imagick::FILTER_LANCZOS, 1);
        $qrPage->compositeImage($qrImage, \Imagick::COMPOSITE_DEFAULT, 150, 180);

        $pages->addImage($qrPage);
        $pages->setImageFormat('pdf');
        $pages->writeImages($targetPath, true);

        $qrImage->clear();
        $qrPage->clear();
        $pages->clear();
    }
}