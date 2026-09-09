<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class DocumentPreviewService
{
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
                $process = new Process([
                    'libreoffice', '-env:UserInstallation=file://'.$working.'/profile',
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
