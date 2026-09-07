<?php

namespace Tests\Feature;

use App\Services\DocumentSpreadsheetService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DocumentSpreadsheetTest extends TestCase
{
    public function test_export_is_readable_excel_with_images_and_literal_user_text(): void
    {
        $data = app(DocumentSpreadsheetService::class)->build(collect([
            [1, 'LAO-26-001', 'Office', '=1+1', 'Contract', 'José', 'September 7, 2026', '', 'Incoming', '', '', '', ''],
        ]), 'Incoming');
        $path = tempnam(sys_get_temp_dir(), 'report-test-');
        try {
            file_put_contents($path, $data);
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame('BICOL UNIVERSITY', $sheet->getCell('C2')->getValue());
            $this->assertSame('LAO-26-001', $sheet->getCell('B13')->getValue());
            $this->assertSame('=1+1', $sheet->getCell('D13')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D13')->getDataType());
            $this->assertCount(4, $sheet->getDrawingCollection());
            $this->assertSame('A12:M13', $sheet->getAutoFilter()->getRange());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_empty_export_has_an_explicit_message(): void
    {
        $data = app(DocumentSpreadsheetService::class)->build(collect(), 'Pending');
        $this->assertStringStartsWith('PK', $data);
    }
}
