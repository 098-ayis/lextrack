<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Throwable;

final class DocumentQrToken
{
    private const PREFIX = 'LEXTRACK-QR-1.';

    public static function encode(Document $document): string
    {
        $encryptedDocumentId = Crypt::encryptString(
            'document:' . $document->getKey()
        );

        return self::PREFIX . rtrim(
            strtr(base64_encode($encryptedDocumentId), '+/', '-_'),
            '='
        );
    }

    public static function decode(string $token): ?int
    {
        $token = trim($token);

        if (strlen($token) > 512 || ! str_starts_with($token, self::PREFIX)) {
            return null;
        }

        $encoded = substr($token, strlen(self::PREFIX));

        if ($encoded === '' || preg_match('/^[A-Za-z0-9_-]+$/', $encoded) !== 1) {
            return null;
        }

        $padding = strlen($encoded) % 4;
        $encoded .= $padding === 0 ? '' : str_repeat('=', 4 - $padding);

        $encrypted = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($encrypted === false) {
            return null;
        }

        try {
            $value = Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }

        if (! preg_match('/^document:([1-9][0-9]*)$/', $value, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Support QR images generated before the token format was introduced.
     * Those QR codes contain a signed /document-status/{id} URL instead.
     */
    public static function decodeSignedStatusUrl(string $value): ?int
    {
        $value = trim($value);

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);

        if (! is_string($path) || preg_match(
            '#/document-status/([1-9][0-9]*)$#',
            $path,
            $matches,
        ) !== 1) {
            return null;
        }

        try {
            $request = Request::create($value, 'GET');
        } catch (Throwable) {
            return null;
        }

        if (! URL::hasValidSignature($request)) {
            return null;
        }

        return (int) $matches[1];
    }
}
