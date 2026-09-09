<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentDownloadService;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use FPDF;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    public function test_docx_download_keeps_existing_template_parts(): void
    {
        Storage::fake('local');
        $path = Storage::disk('local')->path('letterhead.docx');
        copy(base_path('LETTERHEAD-LAO.docx'), $path);
        $hash = hash_file('sha256', $path);
        $response = app(DocumentDownloadService::class)->download(
            (new Document)->forceFill(['document_id' => 42]),
            (new DocumentVersion)->forceFill(['file_path' => 'letterhead.docx'])
        );
        $source = new \ZipArchive;
        $output = new \ZipArchive;
        $source->open($path);
        $output->open($response->getFile()->getPathname());
        try {
            for ($index = 0; $index < $source->numFiles; $index++) {
                $name = $source->getNameIndex($index);
                if (! in_array($name, ['word/document.xml', 'word/_rels/document.xml.rels', '[Content_Types].xml'], true)) {
                    $this->assertSame($source->getFromName($name), $output->getFromName($name), $name);
                }
            }
            foreach (['word/document.xml', 'word/_rels/document.xml.rels', '[Content_Types].xml'] as $name) {
                $this->assertTrue((new \DOMDocument)->loadXML($output->getFromName($name)), $name);
            }
            $this->assertSame($hash, hash_file('sha256', $path));
        } finally {
            $source->close();
            $output->close();
            unlink($response->getFile()->getPathname());
        }
    }

    public function test_docx_download_preserves_word_format_with_qr_and_cleans_working_files(): void
    {
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
            $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
            $this->assertStringContainsString('sample document-with-qr.docx', $response->headers->get('Content-Disposition'));
            $this->assertSame($hash, hash_file('sha256', $path));
            $this->assertDirectoryDoesNotExist(substr($outputPath, 0, -strlen('-with-qr.docx')));
            $output = new \ZipArchive;
            $this->assertTrue($output->open($outputPath));
            try {
                $xml = new \DOMDocument;
                $this->assertTrue($xml->loadXML($output->getFromName('word/document.xml')));
                $xpath = new \DOMXPath($xml);
                $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
                $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $this->assertSame('Word download regression test', $xpath->evaluate('string(//w:t)'));
                $imageId = $xpath->evaluate('string(//a:blip/@r:embed)');
                $this->assertNotEmpty($imageId);
                $relationships = new \DOMDocument;
                $this->assertTrue($relationships->loadXML($output->getFromName('word/_rels/document.xml.rels')));
                $rels = new \DOMXPath($relationships);
                $imagePath = $rels->evaluate('string(//*[@Id="'.$imageId.'"]/@Target)');
                $png = $output->getFromName('word/'.$imagePath);
                $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
                $statusUrl = $rels->evaluate('string(//*[@Id="'.$imageId.'Link"]/@Target)');
                $this->assertStringContainsString('/document-status/42?signature=', $statusUrl);
                $expected = (new QRCode(new QROptions([
                    'outputType' => QROutputInterface::GDIMAGE_PNG,
                    'outputBase64' => false, 'scale' => 10,
                ])))->render($statusUrl);
                $this->assertSame($expected, $png);
            } finally {
                $output->close();
            }
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
