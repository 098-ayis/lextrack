<?php

namespace App\Filament\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;

class ViewDocument extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'View Documents';

    protected static ?string $slug = 'documents/{document}';

    protected string $view = 'filament.pages.view-document';

    public Document $documentRecord;

    /**
     * URL used by the Blade iframe.
     */
    public string $previewUrl = '';

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    /**
     * Hide Filament's built-in page heading.
     */
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function mount(string|int $document): void
    {
        $this->documentRecord = Document::where(
            'document_id',
            $document
        )->firstOrFail();

        $this->previewUrl = $this->generatePreview();
    }

    /**
     * Generate a preview URL for the document.
     *
     * PDF:
     *     Display directly.
     *
     * DOC / DOCX:
     *     Convert to a temporary PDF using LibreOffice.
     */
    protected function generatePreview(): string
    {
        $path = $this->documentRecord->file_path;

        if (!$path) {
            return '';
        }

        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            return '';
        }

        $extension = strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        );

        // PDF
        if ($extension === 'pdf') {
            return route(
                'admin.documents.preview',
                [
                    'document' => $this->documentRecord->document_id,
                ]
            );
        }

        if (!in_array($extension, ['doc', 'docx'], true)) {
            return '';
        }

        $source = $disk->path($path);

        $previewDirectory = storage_path(
            'app/private/temp-previews'
        );

        if (!is_dir($previewDirectory)) {
            mkdir($previewDirectory, 0775, true);
        }

        $previewName = md5($path) . '.pdf';

        $previewPath =
            $previewDirectory . '/' . $previewName;

        if (
            file_exists($previewPath) &&
            filemtime($previewPath) >= filemtime($source)
        ) {
            return route(
                'admin.document.temp-preview',
                ['file' => $previewName]
            );
        }

        if (file_exists($previewPath)) {
            unlink($previewPath);
        }

        $command = sprintf(
            'libreoffice --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($previewDirectory),
            escapeshellarg($source)
        );

        $output = [];
        $exitCode = 0;

        exec(
            $command,
            $output,
            $exitCode
        );

        if ($exitCode !== 0) {
            logger()->error(
                'LibreOffice conversion failed',
                [
                    'document_id' =>
                        $this->documentRecord->document_id,
                    'source' => $source,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]
            );

            return '';
        }

        $generatedPdf =
            $previewDirectory .
            '/' .
            pathinfo($source, PATHINFO_FILENAME) .
            '.pdf';

        if (!file_exists($generatedPdf)) {
            logger()->error(
                'LibreOffice PDF was not generated',
                [
                    'document_id' =>
                        $this->documentRecord->document_id,
                    'expected' => $generatedPdf,
                    'output' => $output,
                ]
            );

            return '';
        }

        if ($generatedPdf !== $previewPath) {
            rename(
                $generatedPdf,
                $previewPath
            );
        }

        return route(
            'admin.document.temp-preview',
            ['file' => $previewName]
        );
    }
}