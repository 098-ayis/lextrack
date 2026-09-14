<?php

namespace App\Filament\Resources\DocumentTypes\Pages;

use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentType extends EditRecord
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return DocumentTypeResource::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Document type updated successfully';
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
