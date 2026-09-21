<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class DocumentPreviewService
{
    /** Return the number of pages in a PDF, or null when it cannot be read. */
    public function pageCount(string $source): ?int
    {
        if (! is_file($source) || strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        $escapedPath = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $source);
        $process = new Process([
            'gs', '-q', '-dSAFER', '-dNODISPLAY',
            '-c', '('.$escapedPath.') (r) file runpdfbegin pdfpagecount = quit',
        ]);
        $process->setTimeout(15);

        try {
            $process->mustRun();
        } catch (\Throwable) {
            return null;
        }

        foreach (array_reverse(preg_split('/\R/', trim($process->getOutput())) ?: []) as $line) {
            $line = trim($line);

            if (ctype_digit($line) && (int) $line > 0) {
                return (int) $line;
            }
        }

        return null;
    }

    /**
     * Build a consistent, first-page thumbnail for document cards.
     *
     * Images and PDFs are normalized to the same 3:4 canvas. PDFs use
     * Ghostscript instead of the browser viewer, whose zoom varies by browser.
     */
    public function thumbnail(string $source): BinaryFileResponse
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (! is_file($source) || (! in_array($extension, $imageExtensions, true) && $extension !== 'pdf')) {
            abort(404);
        }

        $directory = storage_path('app/private/document-thumbnails');
        File::ensureDirectoryExists($directory, 0775, true);

        $sourceHash = hash_file('sha256', $source);
        abort_unless($sourceHash !== false, 404);

        // Version the key so old landscape thumbnails are not served from cache.
        $thumbnailPath = $directory.'/'.hash('sha256', 'portrait-v1:'.$sourceHash).'.jpg';

        if (! is_file($thumbnailPath) || filesize($thumbnailPath) === 0) {
            $working = $directory.'/'.bin2hex(random_bytes(16));
            File::makeDirectory($working, 0700, true);

            try {
                $imageSource = $source;

                if ($extension === 'pdf') {
                    $imageSource = $working.'/page.png';
                    $process = new Process([
                        'gs', '-q', '-dSAFER', '-dBATCH', '-dNOPAUSE',
                        '-dFirstPage=1', '-dLastPage=1', '-dUseCropBox',
                        '-sDEVICE=png16m', '-r96', '-sOutputFile='.$imageSource, $source,
                    ]);
                    $process->setTimeout(120);
                    $process->mustRun();

                    if (! is_file($imageSource) || filesize($imageSource) === 0) {
                        throw new RuntimeException('Unable to render the document thumbnail.');
                    }
                }

                $this->writeThumbnail($imageSource, $working.'/thumbnail.jpg');

                if (! rename($working.'/thumbnail.jpg', $thumbnailPath)) {
                    throw new RuntimeException('Unable to save the document thumbnail.');
                }
            } finally {
                File::deleteDirectory($working);
            }
        }

        return response()->file($thumbnailPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ])->setContentDisposition('inline');
    }

    private function writeThumbnail(string $source, string $destination): void
    {
        $image = new \Imagick();
        $canvas = new \Imagick();

        try {
            $image->readImage($source);
            $image->setIteratorIndex(0);
            $image->autoOrient();
            // Letter/A4 pages are portrait; a portrait target avoids shrinking
            // the page into a wide landscape thumbnail with large side bars.
            $image->thumbnailImage(440, 600, true, true);

            $canvas->newImage(480, 640, new \ImagickPixel('#f3f4f6'), 'jpeg');
            $canvas->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            $canvas->compositeImage(
                $image,
                \Imagick::COMPOSITE_OVER,
                (int) ((480 - $image->getImageWidth()) / 2),
                (int) ((640 - $image->getImageHeight()) / 2),
            );
            $canvas->setImageCompressionQuality(84);
            $canvas->writeImage($destination);
        } finally {
            $image->clear();
            $canvas->clear();
        }
    }

    public function preview(string $source): BinaryFileResponse|Response
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if ($extension === 'docx') {
            return response()->view('documents.docx-preview', [
                'documentData' => base64_encode(File::get($source)),
            ])->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'private, no-store');
        }
        if (! in_array($extension, ['doc', 'docx'], true)) {
            return response()->file($source)->setContentDisposition('inline');
        }

        $directory = storage_path('app/private/temp-previews');
        File::ensureDirectoryExists($directory, 0775, true);
        $path = $directory.'/'.hash('sha256', $source.hash_file('sha256', $source)).'.pdf';

        if (! is_file($path)) {
            $working = $directory.'/'.bin2hex(random_bytes(16));
            File::makeDirectory($working, 0700, true);
            try {
                $profilePath = realpath($working) ?: $working;
                $profileUri = 'file://'.str_replace('%2F', '/', rawurlencode($profilePath)).'/profile';
                $process = new Process([
                    'libreoffice', '-env:UserInstallation='.$profileUri,
                    '--headless', '--convert-to', 'pdf:writer_pdf_Export',
                    '--outdir', $working, $source,
                ]);
                $process->setTimeout(120);
                $process->mustRun();
                $generated = $working.'/'.pathinfo($source, PATHINFO_FILENAME).'.pdf';
                if (! is_file($generated) || file_get_contents($generated, false, null, 0, 5) !== '%PDF-') {
                    throw new RuntimeException('Unable to generate the Word document preview.');
                }
                if (! rename($generated, $path)) {
                    throw new RuntimeException('Unable to save the Word document preview.');
                }
            } finally {
                File::deleteDirectory($working);
            }
        }

        return response()->file($path, ['Content-Type' => 'application/pdf'])->setContentDisposition('inline');
    }
}
