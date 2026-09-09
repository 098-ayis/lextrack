<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class DocumentWordQrService
{
    public function stamp(string $source, string $target, string $qrImage, string $statusUrl): void
    {
        if (! copy($source, $target)) {
            throw new RuntimeException('Unable to copy the Word document.');
        }

        $zip = new ZipArchive;
        if ($zip->open($target) !== true) {
            throw new RuntimeException('Unable to open the Word document.');
        }

        try {
            $document = $this->xml($zip->getFromName('word/document.xml'));
            $types = $this->xml($zip->getFromName('[Content_Types].xml'));
            $relationshipsXml = $zip->getFromName('word/_rels/document.xml.rels');
            $relationships = $this->xml($relationshipsXml === false
                ? '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>'
                : $relationshipsXml);
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
            $body = $xpath->query('/w:document/w:body')->item(0);
            if ($body === null) {
                throw new RuntimeException('The Word document has no supported document body.');
            }

            // Keep existing package parts (styles, headers, images and tables) intact.
            do {
                $id = 'qr'.bin2hex(random_bytes(12));
            } while ($zip->locateName('word/media/'.$id.'.png') !== false
                || (new DOMXPath($relationships))->query('//*[@Id="'.$id.'" or @Id="'.$id.'Link"]')->length > 0);

            $drawingId = 1;
            foreach ($xpath->query('//wp:docPr') as $properties) {
                $drawingId = max($drawingId, (int) $properties->getAttribute('id') + 1);
            }
            foreach (['image' => 'media/'.$id.'.png', 'hyperlink' => $statusUrl] as $type => $destination) {
                $relationship = $relationships->createElementNS($relationships->documentElement->namespaceURI, 'Relationship');
                $relationship->setAttribute('Id', $type === 'image' ? $id : $id.'Link');
                $relationship->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/'.$type);
                $relationship->setAttribute('Target', $destination);
                if ($type === 'hyperlink') {
                    $relationship->setAttribute('TargetMode', 'External');
                }
                $relationships->documentElement->appendChild($relationship);
            }
            $contentType = $types->createElementNS($types->documentElement->namespaceURI, 'Override');
            $contentType->setAttribute('PartName', '/word/media/'.$id.'.png');
            $contentType->setAttribute('ContentType', 'image/png');
            $types->documentElement->appendChild($contentType);

            // An inline image reserves its own space so it cannot cover document text.
            $paragraph = $this->xml(<<<XML
                <w:p xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
                     xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
                     xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
                     xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
                     xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
                  <w:pPr><w:spacing w:before="0" w:after="0"/><w:jc w:val="right"/></w:pPr>
                  <w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">
                    <wp:extent cx="900000" cy="900000"/>
                    <wp:docPr id="{$drawingId}" name="Document status QR code" descr="Scan to view document status">
                      <a:hlinkClick r:id="{$id}Link"/>
                    </wp:docPr>
                    <wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>
                    <a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                      <pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="{$id}.png"/><pic:cNvPicPr/></pic:nvPicPr>
                        <pic:blipFill><a:blip r:embed="{$id}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>
                        <pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="900000" cy="900000"/></a:xfrm>
                          <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
                        </pic:spPr>
                      </pic:pic>
                    </a:graphicData></a:graphic>
                  </wp:inline></w:drawing></w:r>
                </w:p>
                XML);
            $body->insertBefore($document->importNode($paragraph->documentElement, true), $body->firstChild);

            foreach ([
                'word/media/'.$id.'.png' => $qrImage,
                'word/document.xml' => $document->saveXML(),
                'word/_rels/document.xml.rels' => $relationships->saveXML(),
                '[Content_Types].xml' => $types->saveXML(),
            ] as $name => $contents) {
                if (! $zip->addFromString($name, $contents)) {
                    throw new RuntimeException('Unable to add the QR code to the Word document.');
                }
            }
        } finally {
            if (! $zip->close()) {
                throw new RuntimeException('Unable to save the Word document.');
            }
        }
    }

    private function xml(string|false $contents): DOMDocument
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            if ($contents === false || ! $document->loadXML($contents, LIBXML_NONET) || $document->doctype !== null) {
                throw new RuntimeException('The Word document contains invalid XML.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $document;
    }
}
