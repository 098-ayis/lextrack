<?php

namespace App\Http\Controllers;

use App\Models\Document;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class DocumentQrCodeController extends Controller
{
    public function __invoke(int $document): Response
    {
        Document::query()->findOrFail($document);
        $url = URL::signedRoute('documents.public-status', ['document' => $document]);
        $png = (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'scale' => 5,
        ])))->render($url);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="document-' . $document . '-qr.png"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
