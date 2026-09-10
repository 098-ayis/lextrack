<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Crypt;
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
}
