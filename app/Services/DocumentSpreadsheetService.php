<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class DocumentSpreadsheetService
{
    public function build(Collection $rows, string $section): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Documents Report');
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $widths = [7, 19, 25, 45, 23, 25, 20, 25, 18, 20, 25, 20, 20];
        foreach ($widths as $i => $width) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($width);
        }
        $text = function (string $range, string $value) use ($sheet): void {
            $sheet->mergeCells($range);
            $sheet->setCellValueExplicit(explode(':', $range)[0], $value, DataType::TYPE_STRING);
        };
        $text('C1:I1', 'REPUBLIC OF THE PHILIPPINES');
        $text('C2:I2', 'BICOL UNIVERSITY');
        $text('C3:I3', 'LEGAL AFFAIRS OFFICE');
        $text('C4:I4', 'Legazpi City | Email: op@bicol-u.edu.ph');
        $text('C5:I5', 'MANILA OFFICE:');
        $text('C6:I6', 'No. 4 Lopez St., M. H. del Pilar, Roosevelt Ave., Quezon City');
        $text('C7:I7', 'Telefax: (02) 921-1586');
        $text('A9:M9', 'Legal Affairs Office');
        $text('A10:M10', 'DOCUMENTS REPORT — '.strtoupper($section));
        $text('A11:M11', 'Generated '.now()->format('F d, Y h:i A'));
        for ($r = 1; $r <= 11; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(23);
        }
        $sheet->getStyle('C2')->getFont()->setSize(24)->setBold(true)->getColor()->setRGB('183963');
        $sheet->getStyle('C3')->getFont()->setSize(15)->setBold(true);
        $sheet->getStyle('A9')->getFont()->setItalic(true)->getColor()->setRGB('183963');
        $sheet->getStyle('A10:M11')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A10')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A8:M8')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $draw = function (string $file, string $cell, int $height) use ($sheet): void {
            $image = new Drawing;
            $image->setName(pathinfo($file, PATHINFO_FILENAME));
            $image->setPath(public_path('images/reports/'.$file));
            $image->setCoordinates($cell)->setHeight($height)->setOffsetX(8)->setOffsetY(4);
            $image->setWorksheet($sheet);
        };
        $draw('bu-certified.png', 'A1', 205);
        $draw('sdg.png', 'J2', 145);
        $draw('bagong-pilipinas.png', 'L2', 145);
        $headers = ['No.', 'LAO No.', 'Office / Unit', 'Particulars', 'Document Type', 'Uploaded By', 'Upload Date', 'Action Taken', 'Status', 'Outgoing Date', 'Sent To', 'Sent Date', 'Latest Updated'];
        $sheet->fromArray($headers, null, 'A12');
        foreach ($rows as $index => $row) {
            foreach ($row as $column => $value) {
                // User-entered values must stay text, including values beginning with '='.
                $sheet->setCellValueExplicit([$column + 1, $index + 13], (string) ($value ?? ''), DataType::TYPE_STRING);
            }
            $sheet->getRowDimension($index + 13)->setRowHeight(60);
        }
        $last = max(13, $rows->count() + 12);
        if ($rows->isEmpty()) {
            $text('A13:M13', 'No documents match the selected filters.');
        }
        $sheet->getStyle("A12:M{$last}")->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getStyle("A12:M{$last}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A12:M12')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A12:M12')->getFill()->setFillType('solid')->getStartColor()->setRGB('183963');
        $sheet->getRowDimension(12)->setRowHeight(30);
        $sheet->freezePane('C13');
        if ($rows->isNotEmpty()) {
            $sheet->setAutoFilter("A12:M{$last}");
        }
        $footer = $last + 3;
        $draw('qr.png', 'A'.$footer, 80);
        $text("C{$footer}:J".($footer + 2), 'A University for Humanity characterized by productive scholarship, transformative leadership, collaborative service and distinctive character for sustainable societies.');
        $sheet->getStyle("C{$footer}:J".($footer + 2))->getAlignment()->setWrapText(true)->setHorizontal('center')->setVertical('center');
        $sheet->getStyle("C{$footer}:J{$footer}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('ED7D31');
        $text("K{$footer}:M".($footer + 2), "This communication is aligned to\nSDG No. ______");
        $sheet->getStyle("K{$footer}:M".($footer + 2))->getAlignment()->setWrapText(true);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A3)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 12)->setPrintArea('A1:M'.($footer + 3));
        $sheet->getHeaderFooter()->setOddFooter('&LLegal Affairs Office — Bicol University&RPage &P of &N');
        $sheet->setShowGridlines(false);
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new RuntimeException('Unable to open spreadsheet stream.');
        }
        try {
            (new Xlsx($book))->save($stream);
            rewind($stream);
            $result = stream_get_contents($stream);
            if ($result === false) {
                throw new RuntimeException('Unable to read spreadsheet output.');
            }

            return $result;
        } finally {
            fclose($stream);
            $book->disconnectWorksheets();
        }
    }
}
