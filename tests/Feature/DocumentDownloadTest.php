<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentDownloadService;
use FPDF;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    public function test_word_download_converts_to_pdf_with_qr_and_cleans_working_files(): void
    {
        $check = new Process(['libreoffice', '--version']);
        $check->run();
        if (! $check->isSuccessful()) {
            $this->markTestSkipped('LibreOffice is required for Word downloads.');
        }

        Storage::fake('local');
        $disk = Storage::disk('local');
        $path = $disk->path('sample document.docx');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Word download regression test</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        $hash = hash_file('sha256', $path);
        $document = (new Document)->forceFill(['document_id' => 42]);
        $version = (new DocumentVersion)->forceFill(['file_path' => 'sample document.docx']);
        $response = app(DocumentDownloadService::class)->download($document, $version);
        $outputPath = $response->getFile()->getPathname();
        try {
            $pdf = new Fpdi;
            $this->assertSame(1, $pdf->setSourceFile($outputPath));
            $this->assertSame($hash, hash_file('sha256', $path));
            $this->assertStringContainsString('/document-status/42?signature=', file_get_contents($outputPath));
            $this->assertDirectoryDoesNotExist(substr($outputPath, 0, -strlen('-with-qr.pdf')));
        } finally {
            unlink($outputPath);
        }
    }

    public function test_download_preserves_pdf_text_page_sizes_and_source(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'qr-source-');
        $target = tempnam(sys_get_temp_dir(), 'qr-target-');

        try {
            $original = new FPDF;
            $original->SetCompression(false);
            $original->AddPage('P', [210, 297]);
            $original->SetFont('Helvetica', '', 12);
            $original->Text(20, 28, 'Original selectable document text');
            $original->AddPage('L', [210, 297]);
            $original->Text(20, 28, 'Second page stays landscape');
            $original->Output('F', $source);
            $originalHash = hash_file('sha256', $source);

            $method = new \ReflectionMethod(DocumentDownloadService::class, 'stampQrCode');
            $method->invoke(new DocumentDownloadService, $source, $target, 42);

            $this->assertSame($originalHash, hash_file('sha256', $source));
            $pdf = new Fpdi;
            $this->assertSame(2, $pdf->setSourceFile($target));
            $first = $pdf->getTemplateSize($pdf->importPage(1));
            $second = $pdf->getTemplateSize($pdf->importPage(2));
            $this->assertEqualsWithDelta(210, $first['width'], 0.01);
            $this->assertEqualsWithDelta(297, $first['height'], 0.01);
            $this->assertEqualsWithDelta(297, $second['width'], 0.01);
            $this->assertEqualsWithDelta(210, $second['height'], 0.01);

            $output = file_get_contents($target);
            // Imported page streams retain actual PDF text operators, not screenshots.
            $this->assertStringContainsString('(Original selectable document text) Tj', $output);
            $this->assertStringContainsString('(Second page stays landscape) Tj', $output);
            $this->assertSame(1, substr_count($output, '/Subtype /Image'));
            $this->assertStringContainsString('/document-status/42?signature=', $output);
        } finally {
            unlink($source);
            unlink($target);
        }
    }
}
