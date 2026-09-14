<?php

namespace Tests\Feature;

use App\Services\MonthlyReportWordService;
use Tests\TestCase;
use ZipArchive;

class MonthlyReportWordTest extends TestCase
{
    public function test_letter_and_long_exports_keep_the_template_and_requested_page_size(): void
    {
        $header = $footer = null;
        foreach (['letter' => 15840, 'long' => 18720] as $paper => $height) {
            $bytes = app(MonthlyReportWordService::class)->build([
                'month' => 'September 2026', 'received' => 4, 'processed' => 3,
                'completed' => 2, 'requests' => 1, 'activities' => collect(), 'paperSize' => $paper,
            ], 'Staff');
            $path = tempnam(sys_get_temp_dir(), 'paper-test-');
            file_put_contents($path, $bytes);
            $zip = new ZipArchive;
            try {
                $zip->open($path);
                $xml = new \DOMDocument;
                $xml->loadXML($zip->getFromName('word/document.xml'));
                $size = $xml->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'pgSz')->item(0);
                $this->assertSame('12240', $size->getAttribute('w:w'));
                $this->assertSame((string) $height, $size->getAttribute('w:h'));
                $header ??= $zip->getFromName('word/header.xml');
                $footer ??= $zip->getFromName('word/footer.xml');
                $this->assertSame($header, $zip->getFromName('word/header.xml'));
                $this->assertSame($footer, $zip->getFromName('word/footer.xml'));
            } finally {
                $zip->close();
                unlink($path);
            }
        }
    }

    public function test_word_export_contains_editable_text_and_tables(): void
    {
        $hash = hash_file('sha256', base_path('LETTERHEAD-LAO.docx'));
        $bytes = app(MonthlyReportWordService::class)->build([
            'month' => 'September 2026', 'received' => 4, 'processed' => 3,
            'completed' => 2, 'requests' => 1, 'activities' => collect(),
        ], 'Admin & Staff');
        $path = tempnam(sys_get_temp_dir(), 'word-test-');
        file_put_contents($path, $bytes);
        $zip = new ZipArchive;
        $template = new ZipArchive;
        try {
            $this->assertTrue($zip->open($path));
            $template->open(base_path('LETTERHEAD-LAO.docx'));
            $xml = $zip->getFromName('word/document.xml');
            $this->assertStringContainsString('MONTHLY ACCOMPLISHMENT REPORT', $xml);
            $this->assertStringContainsString('Admin &amp; Staff', $xml);
            $this->assertStringContainsString('<w:tbl>', $xml);
            $this->assertStringContainsString('Date accomplished', $xml);
            $this->assertStringContainsString('w:headerReference', $xml);
            $this->assertStringContainsString('w:footerReference', $xml);
            $this->assertStringNotContainsString('BICOL UNIVERSITY', $xml);
            $this->assertStringContainsString('BICOL UNIVERSITY', $zip->getFromName('word/header.xml'));
            $this->assertStringContainsString('A University for Humanity', $zip->getFromName('word/footer.xml'));
            $this->assertFalse($zip->locateName('word/media/page-1.png'));
            $this->assertNotFalse($zip->locateName('word/media/bu-certified.png'));
            $this->assertStringContainsString('w:val="183963"', $zip->getFromName('word/header.xml'));
            $this->assertStringContainsString('<w:i/>', $zip->getFromName('word/header.xml'));
            $this->assertStringContainsString('w:color="ED7D31"', $zip->getFromName('word/footer.xml'));
            $this->assertStringContainsString('w:val="double"', $zip->getFromName('word/footer.xml'));
            $this->assertStringContainsString('w:color="0099FF"', $zip->getFromName('word/footer.xml'));
            $document = new \DOMDocument;
            $this->assertTrue($document->loadXML(trim($xml)));
            $this->assertSame($hash, hash_file('sha256', base_path('LETTERHEAD-LAO.docx')));
        } finally {
            $zip->close();
            $template->close();
            unlink($path);
        }
    }
}
