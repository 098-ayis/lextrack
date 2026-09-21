<?php

namespace App\Rules;

use App\Models\DocumentVersion;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueDocumentVersionUpload implements ValidationRule
{
    public function __construct(
        private readonly int $documentId,
        private readonly ?int $userId,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fileHash = DocumentVersion::hashForUpload($value);

        if (
            $fileHash !== null
            && DocumentVersion::existsForDocumentOrUserHash(
                $this->documentId,
                $fileHash,
                $this->userId,
            )
        ) {
            $fail('This file has already been uploaded and cannot be added again.');
        }
    }
}
