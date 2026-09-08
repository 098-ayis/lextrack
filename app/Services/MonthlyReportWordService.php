<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class MonthlyReportWordService
{
    public function build(array $report, string $preparedBy): string
    {
        $path = tempnam(sys_get_temp_dir(), 'monthly-word-');
        $zip = new ZipArchive;
        try {
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the Word report.');
            }
            try {
                $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/header.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/><Override PartName="/word/footer.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/></Types>');
                $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
                $relationships = '';
                foreach (['bu-certified', 'sdg', 'bagong-pilipinas', 'qr'] as $index => $name) {
                    $zip->addFile(public_path('images/reports/'.$name.'.png'), 'word/media/'.$name.'.png');
                    $relationships .= '<Relationship Id="rId'.($index + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$name.'.png"/>';
                }
                $document = new DOMDocument;
                $document->loadXML(trim(view('reports.monthly-word', $report + ['preparedBy' => $preparedBy])->render()));
                $xpath = new DOMXPath($document);
                $wordNamespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
                $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
                $xpath->registerNamespace('w', $wordNamespace);
                $xpath->registerNamespace('r', $relationshipNamespace);
                foreach (['header' => 1, 'footer' => 4] as $part => $imageId) {
                    $nodes = iterator_to_array($xpath->query('/w:document/w:body/w:tbl[.//*[@r:embed="rId'.$imageId.'"]]'));
                    $partDocument = new DOMDocument('1.0', 'UTF-8');
                    $root = $partDocument->createElementNS($wordNamespace, $part === 'header' ? 'w:hdr' : 'w:ftr');
                    $partDocument->appendChild($root);
                    foreach ($nodes as $index => $node) {
                        if ($index === 0) {
                            $root->appendChild($partDocument->importNode($node, true));
                        }
                        $node->parentNode->removeChild($node);
                    }
                    if ($part === 'header') {
                        $labels = iterator_to_array($xpath->query('/w:document/w:body/w:p[w:r/w:t="Legal Affairs Office"]'));
                        foreach ($labels as $index => $label) {
                            if ($index === 0) {
                                $root->appendChild($partDocument->importNode($label, true));
                            }
                            $label->parentNode->removeChild($label);
                        }
                    }
                    $partXPath = new DOMXPath($partDocument);
                    $partXPath->registerNamespace('w', $wordNamespace);
                    $styleRuns = function ($paragraph, string $color, bool $italic) use ($partDocument, $partXPath, $wordNamespace): void {
                        foreach ($partXPath->query('.//w:rPr', $paragraph) as $properties) {
                            $colorNode = $partDocument->createElementNS($wordNamespace, 'w:color');
                            $colorNode->setAttributeNS($wordNamespace, 'w:val', $color);
                            $properties->appendChild($colorNode);
                            if ($italic) {
                                $properties->appendChild($partDocument->createElementNS($wordNamespace, 'w:i'));
                            }
                        }
                    };
                    if ($part === 'header') {
                        foreach ($partXPath->query('//w:p[w:r/w:t="Legal Affairs Office"]') as $label) {
                            $styleRuns($label, '183963', true);
                        }
                        foreach ($partXPath->query('//w:p[w:r/w:t="BICOL UNIVERSITY"]') as $label) {
                            $styleRuns($label, '183963', false);
                        }
                        $line = $partXPath->query('//w:tblPr/w:tblBorders/w:bottom')->item(0);
                        $line->setAttributeNS($wordNamespace, 'w:val', 'single');
                        $line->setAttributeNS($wordNamespace, 'w:color', '111111');
                        $line->setAttributeNS($wordNamespace, 'w:sz', '8');
                    } else {
                        $cells = $partXPath->query('//w:tr/w:tc');
                        // Compact footer typography and spacing, matching the approved sample.
                        foreach ($partXPath->query('//w:pPr/w:spacing') as $spacing) {
                            $spacing->setAttributeNS($wordNamespace, 'w:after', '0');
                            $spacing->setAttributeNS($wordNamespace, 'w:before', '0');
                            $spacing->setAttributeNS($wordNamespace, 'w:line', '200');
                            $spacing->setAttributeNS($wordNamespace, 'w:lineRule', 'auto');
                        }
                        foreach ($partXPath->query('.//w:rFonts', $cells->item(3)) as $font) {
                            $font->setAttributeNS($wordNamespace, 'w:ascii', 'Arial');
                            $font->setAttributeNS($wordNamespace, 'w:hAnsi', 'Arial');
                        }
                        foreach ($partXPath->query('.//w:pPr/w:spacing', $cells->item(3)) as $index => $spacing) {
                            $spacing->setAttributeNS($wordNamespace, 'w:before', $index === 1 ? '140' : '0');
                        }
                        $motto = $cells->item(1);
                        $mottoProperties = $partXPath->query('./w:tcPr', $motto)->item(0);
                        $padding = $mottoProperties->appendChild($partDocument->createElementNS($wordNamespace, 'w:tcMar'));
                        $topPadding = $padding->appendChild($partDocument->createElementNS($wordNamespace, 'w:top'));
                        $topPadding->setAttributeNS($wordNamespace, 'w:w', '114');
                        $topPadding->setAttributeNS($wordNamespace, 'w:type', 'dxa');

                        foreach ($partXPath->query('.//w:p', $motto) as $paragraph) {
                            $styleRuns($paragraph, '111111', true);
                        }
                        foreach ([1 => ['top'], 3 => ['top', 'left', 'bottom', 'right']] as $index => $edges) {
                            $cell = $cells->item($index);
                            $properties = $partXPath->query('./w:tcPr', $cell)->item(0);
                            $borders = $properties->appendChild($partDocument->createElementNS($wordNamespace, 'w:tcBorders'));
                            foreach ($edges as $edge) {
                                $border = $borders->appendChild($partDocument->createElementNS($wordNamespace, 'w:'.$edge));
                                $border->setAttributeNS($wordNamespace, 'w:val', $index === 1 ? 'single' : 'double');
                                $border->setAttributeNS($wordNamespace, 'w:color', $index === 1 ? 'ED7D31' : '0099FF');
                                $border->setAttributeNS($wordNamespace, 'w:sz', $index === 1 ? '18' : '6');
                            }
                            if ($index === 3) {
                                foreach ($partXPath->query('.//w:p', $cell) as $paragraph) {
                                    $styleRuns($paragraph, '007AC2', false);
                                }
                            }
                        }
                    }
                    if ($part === 'footer') {
                        // A nested table gives the SDG box its own height instead of
                        // stretching its border to match the QR image's row height.
                        $outer = $cells->item(3);
                        $outerProperties = $partXPath->query('./w:tcPr', $outer)->item(0);
                        $borders = $partXPath->query('./w:tcBorders', $outerProperties)->item(0);
                        $table = $partDocument->createElementNS($wordNamespace, 'w:tbl');
                        $properties = $table->appendChild($partDocument->createElementNS($wordNamespace, 'w:tblPr'));
                        $width = $properties->appendChild($partDocument->createElementNS($wordNamespace, 'w:tblW'));
                        $width->setAttributeNS($wordNamespace, 'w:w', '1644');
                        $width->setAttributeNS($wordNamespace, 'w:type', 'dxa');
                        $grid = $table->appendChild($partDocument->createElementNS($wordNamespace, 'w:tblGrid'));
                        $column = $grid->appendChild($partDocument->createElementNS($wordNamespace, 'w:gridCol'));
                        $column->setAttributeNS($wordNamespace, 'w:w', '1644');
                        $row = $table->appendChild($partDocument->createElementNS($wordNamespace, 'w:tr'));
                        $inner = $row->appendChild($partDocument->createElementNS($wordNamespace, 'w:tc'));
                        $innerProperties = $inner->appendChild($partDocument->createElementNS($wordNamespace, 'w:tcPr'));
                        $innerProperties->appendChild($borders);
                        $margins = $innerProperties->appendChild($partDocument->createElementNS($wordNamespace, 'w:tcMar'));
                        foreach (['top', 'left', 'bottom', 'right'] as $edge) {
                            $margin = $margins->appendChild($partDocument->createElementNS($wordNamespace, 'w:'.$edge));
                            $margin->setAttributeNS($wordNamespace, 'w:w', '90');
                            $margin->setAttributeNS($wordNamespace, 'w:type', 'dxa');
                        }
                        foreach (iterator_to_array($partXPath->query('./w:p', $outer)) as $paragraph) {
                            $inner->appendChild($paragraph);
                        }
                        $text = $partXPath->query('.//w:t', $inner)->item(0);
                        $text->nodeValue = 'This communication is';
                        $run = $text->parentNode;
                        $run->appendChild($partDocument->createElementNS($wordNamespace, 'w:br'));
                        $run->appendChild($partDocument->createElementNS($wordNamespace, 'w:t', 'aligned to'));
                        $outer->appendChild($table);
                        $outer->appendChild($partDocument->createElementNS($wordNamespace, 'w:p'));
                    }
                    $zip->addFromString('word/'.$part.'.xml', $partDocument->saveXML());
                    $zip->addFromString('word/_rels/'.$part.'.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'</Relationships>');
                }
                $section = $xpath->query('//w:sectPr')->item(0);
                foreach (['header', 'footer'] as $part) {
                    $reference = $document->createElementNS($wordNamespace, 'w:'.$part.'Reference');
                    $reference->setAttributeNS($wordNamespace, 'w:type', 'default');
                    $reference->setAttributeNS($relationshipNamespace, 'r:id', $part);
                    $section->insertBefore($reference, $section->firstChild);
                    $relationships .= '<Relationship Id="'.$part.'" Type="'.$relationshipNamespace.'/'.$part.'" Target="'.$part.'.xml"/>';
                }
                $margins = $xpath->query('//w:sectPr/w:pgMar')->item(0);
                // Reserve space for the repeated template independently of editable content.
                foreach (['top' => 3300, 'bottom' => 1800, 'header' => 397, 'footer' => 454] as $name => $value) {
                    $margins->setAttributeNS($wordNamespace, 'w:'.$name, (string) $value);
                }
                $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'</Relationships>');
                $zip->addFromString('word/document.xml', $document->saveXML());

            } finally {
                $zip->close();
            }

            return file_get_contents($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
