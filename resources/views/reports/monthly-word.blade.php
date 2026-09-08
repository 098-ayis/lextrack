@php
    $escape = fn ($value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $p = function ($text, $bold = false, $size = 20, $align = 'left') use ($escape) {
        return '<w:p><w:pPr><w:jc w:val="'.$align.'"/><w:spacing w:after="100"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Liberation Serif" w:hAnsi="Liberation Serif"/>'.($bold ? '<w:b/>' : '').'<w:sz w:val="'.$size.'"/></w:rPr><w:t xml:space="preserve">'.$escape($text).'</w:t></w:r></w:p>';
    };
    $drawingId = 0;
    $image = function ($id, $width, $height) use (&$drawingId) {
        $drawingId++;
        $x = (int) ($width * 36000); $y = (int) ($height * 36000);
        return '<w:p><w:r><w:drawing><wp:inline><wp:extent cx="'.$x.'" cy="'.$y.'"/><wp:docPr id="'.$drawingId.'" name="Official mark"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="Official mark"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rId'.$id.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$x.'" cy="'.$y.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    };
    $table = function ($rows, $widths, $border = true) {
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="'.array_sum($widths).'" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders>';
        foreach (['top','left','bottom','right','insideH','insideV'] as $edge) { $xml .= '<w:'.$edge.' w:val="'.($border ? 'single' : 'nil').'" w:sz="4" w:color="AAAAAA"/>'; }
        $xml .= '</w:tblBorders><w:tblCellMar><w:top w:w="70" w:type="dxa"/><w:left w:w="90" w:type="dxa"/><w:bottom w:w="70" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>';
        foreach ($widths as $width) { $xml .= '<w:gridCol w:w="'.$width.'"/>'; }
        $xml .= '</w:tblGrid>';
        foreach ($rows as $row) {
            $xml .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>';
            foreach ($row as $i => $cell) { $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.$widths[$i].'" w:type="dxa"/></w:tcPr>'.$cell.'</w:tc>'; }
            $xml .= '</w:tr>';
        }
        return $xml.'</w:tbl>';
    };
    $pages = $activities->chunk(5);
    if ($pages->isEmpty()) { $pages = collect([collect()]); }
@endphp
{!! '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' !!}
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><w:body>
@foreach($pages as $entries)
@if(!$loop->first)<w:p><w:r><w:br w:type="page"/></w:r></w:p>@endif
{!! $table([[$image(1,29,35), $p('REPUBLIC OF THE PHILIPPINES',false,16).$p('BICOL UNIVERSITY',true,32).$p('LEGAL AFFAIRS OFFICE',true,22).$p('Legazpi City | Email: op@bicol-u.edu.ph',false,14).$p('MANILA OFFICE:',true,14).$p('No. 4 Lopez St., M. H. del Pilar, Roosevelt Ave., Quezon City',false,13).$p('Telefax: (02) 921-1586',false,13), $image(2,23,23), $image(3,23,23)]], [1800,6200,1600,1600], false) !!}
{!! $p('Legal Affairs Office',false,26) !!}
{!! $p('MONTHLY ACCOMPLISHMENT REPORT',true,30,'center') !!}
{!! $p($month,false,22,'center') !!}
@if($loop->first)
{!! $table([[$p($received,true,36).$p('Documents received',false,18),$p($processed,true,36).$p('Documents processed',false,18),$p($completed,true,36).$p('Documents completed',false,18),$p($requests,true,36).$p('Requests processed',false,18)]],[2800,2800,2800,2800]) !!}
{!! $p('Processed counts unique documents with recorded acceptance, update, outgoing, rejection, return, or completion actions during this month. Completed counts unique documents with a recorded completion action or status change. Requests count accepted or rejected requests by processing date. Counts can overlap; downloads and views are excluded. Missing historical logs are not inferred from current status.',false,18) !!}
@endif
@php
$rows = [[$p('Date',true,18),$p('Document / type',true,18),$p('Accomplishment',true,18)]];
foreach ($entries as $entry) {
    $rows[] = [$p($entry->created_at->format('M d, Y'),false,18),$p($entry->document?->lao_number ?? 'Unavailable document',false,18).$p($entry->document?->document_type ?? '',false,18),$p($entry->action_type,true,18).$p(\Illuminate\Support\Str::limit($entry->action_details,180),false,18)];
}
if ($entries->isEmpty()) { $rows[] = [$p(''),$p(''),$p('No processing activities recorded for this month.',false,18)]; }
@endphp
{!! $table($rows,[1904,3024,6272]) !!}
@if($loop->last){!! $p('Prepared by: '.$preparedBy) !!}@endif
{!! $p('Generated '.now()->format('M d, Y H:i').' · Page '.$loop->iteration.' of '.$pages->count(),false,18,'right') !!}
{!! $table([[$image(4,14,14),$p('A University for Humanity characterized by productive scholarship, transformative leadership, collaborative service and distinctive character for sustainable societies.',false,18,'center'),$p(''),$p('This communication is aligned to',false,11).$p('SDG No. ______',true,16)]],[1100,8030,170,1900],false) !!}
@endforeach
<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="397" w:right="340" w:bottom="454" w:left="340" w:header="0" w:footer="0"/></w:sectPr>
</w:body></w:document>
