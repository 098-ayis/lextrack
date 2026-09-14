<?php

namespace App\Filament\Resources\DocumentTypes\Pages;

use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentType extends CreateRecord
{
    protected static string $resource = DocumentTypeResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return DocumentTypeResource::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Document type created successfully';
    }
}
