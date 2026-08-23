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

        /*
        |--------------------------------------------------------------------------
        | Use the public disk
        |--------------------------------------------------------------------------
        */

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return '';
        }

        $extension = strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        );

        /*
        |--------------------------------------------------------------------------
        | PDF - display directly
        |--------------------------------------------------------------------------
        */

        if ($extension === 'pdf') {
            return $disk->url($path);
        }

        /*
        |--------------------------------------------------------------------------
        | Only Word documents need conversion
        |--------------------------------------------------------------------------
        */

        if (!in_array($extension, ['doc', 'docx'], true)) {
            return '';
        }

        /*
        |--------------------------------------------------------------------------
        | Original Word file
        |--------------------------------------------------------------------------
        */

        $source = $disk->path($path);

        /*
        |--------------------------------------------------------------------------
        | Temporary preview directory
        |--------------------------------------------------------------------------
        */

        $previewDirectory = storage_path(
            'app/public/temp-previews'
        );

        if (!is_dir($previewDirectory)) {
            mkdir($previewDirectory, 0775, true);
        }

        /*
        |--------------------------------------------------------------------------
        | Unique temporary PDF
        |--------------------------------------------------------------------------
        */

        $previewName = md5($path) . '.pdf';

        $previewPath = $previewDirectory
            . '/'
            . $previewName;

        /*
        |--------------------------------------------------------------------------
        | Reuse existing preview if original hasn't changed
        |--------------------------------------------------------------------------
        */

        if (
            file_exists($previewPath) &&
            filemtime($previewPath) >= filemtime($source)
        ) {
            return asset(
                'storage/temp-previews/' . $previewName
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Remove outdated preview
        |--------------------------------------------------------------------------
        */

        if (file_exists($previewPath)) {
            unlink($previewPath);
        }

        /*
        |--------------------------------------------------------------------------
        | Convert Word -> PDF
        |--------------------------------------------------------------------------
        */

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
            logger()->error('LibreOffice conversion failed', [
                'document_id' => $this->documentRecord->document_id,
                'source' => $source,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            return '';
        }

        /*
        |--------------------------------------------------------------------------
        | LibreOffice output filename
        |--------------------------------------------------------------------------
        */

        $generatedPdf = $previewDirectory
            . '/'
            . pathinfo($source, PATHINFO_FILENAME)
            . '.pdf';

        if (!file_exists($generatedPdf)) {
            logger()->error('LibreOffice PDF was not generated', [
                'document_id' => $this->documentRecord->document_id,
                'expected' => $generatedPdf,
                'output' => $output,
            ]);

            return '';
        }

        /*
        |--------------------------------------------------------------------------
        | Rename to our unique preview filename
        |--------------------------------------------------------------------------
        */

        if ($generatedPdf !== $previewPath) {
            rename(
                $generatedPdf,
                $previewPath
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Browser-accessible URL
        |--------------------------------------------------------------------------
        */

        return asset(
            'storage/temp-previews/' . $previewName
        );
    }
}