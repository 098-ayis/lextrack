<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use App\Models\DocumentRequest;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

class Request extends Page
{
    use WithFileUploads;

    protected static ?string $slug = 'request-document';

    protected static ?string $navigationLabel = 'Request';

    protected static ?string $title = 'Request A Document';

    protected string $view = 'filament.client.pages.request';

    public string $laoNumber = '';

    public string $purpose = '';

    public string $otherPurpose = '';

    public $attachment = null;

    public function getHeading(): string
    {
        return '';
    }

    /**
     * Documents supplied by the Legal Affairs Office can be requested by LAO
     * number or QR code. The signed-in client is stored on document_requests,
     * not on the document itself.
     */
    protected function requestableDocumentsQuery(): Builder
    {
        return Document::query()
            ->whereNotNull('lao_number')
            ->where('lao_number', '!=', '')
            ->orderBy('lao_number');
    }

    public function updatedLaoNumber(string $laoNumber): void
    {
        $this->resetValidation('laoNumber');
    }

    public function updatedPurpose(string $purpose): void
    {
        $this->resetValidation('purpose');

        if ($purpose !== 'other') {
            $this->otherPurpose = '';
            $this->resetValidation('otherPurpose');
        }
    }

    public function updatedOtherPurpose(): void
    {
        $this->resetValidation('otherPurpose');
    }

    public function purposeOptions(): array
    {
        return [
            'physical_copy' => 'I need a copy of the physical file submitted to the office',
            'personal_reference' => 'For personal reference',
            'official_transaction' => 'For an official transaction or submission',
            'lost_or_damaged' => 'My copy was lost or damaged',
            'other' => 'Others',
        ];
    }

    /**
     * Resolve QR payloads containing an LAO number, a URL with an LAO parameter,
     * JSON with an lao_number field, or a document ID as a last resort.
     */
    public function resolveQr(string $payload): bool
    {
        $payload = trim($payload);
        $laoNumber = $this->extractLaoNumber($payload);

        $query = $this->requestableDocumentsQuery();

        $document = $laoNumber
            ? $query->where('lao_number', $laoNumber)->first()
            : null;

        $documentId = $this->extractDocumentId($payload);

        if (! $document && $documentId !== null) {
            $document = $this->requestableDocumentsQuery()
                ->whereKey($documentId)
                ->first();
        }

        if (! $document && ctype_digit($payload)) {
            $document = $this->requestableDocumentsQuery()
                ->whereKey((int) $payload)
                ->first();
        }

        if (! $document) {
            Notification::make()
                ->title('Document not found')
                ->body('The QR code does not match an available document with an LAO number.')
                ->danger()
                ->send();

            return false;
        }

        $this->laoNumber = (string) $document->lao_number;

        Notification::make()
            ->title('Document selected')
            ->body('LAO number ' . $document->lao_number . ' was found.')
            ->success()
            ->send();

        return true;
    }

    public function submit(): void
    {
        $this->validate([
            'laoNumber' => [
                'required',
                'string',
                'max:50',
                Rule::exists('documents', 'lao_number'),
            ],
            'purpose' => [
                'required',
                Rule::in(array_keys($this->purposeOptions())),
            ],
            'otherPurpose' => [
                'nullable',
                'required_if:purpose,other',
                'string',
                'max:2000',
            ],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx',
                'max:2048',
            ],
        ]);

        $document = $this->requestableDocumentsQuery()
            ->where('lao_number', trim($this->laoNumber))
            ->first();

        if (! $document) {
            $this->addError('laoNumber', 'The LAO number does not match an available document.');

            return;
        }

        $alreadyRequested = DocumentRequest::query()
            ->where('document_id', $document->document_id)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->exists();

        if ($alreadyRequested) {
            Notification::make()
                ->title('Request already submitted')
                ->body('You already have a pending request for this document.')
                ->warning()
                ->send();

            return;
        }

        $attachmentPath = $this->attachment
            ? $this->attachment->store('document-request-attachments', 'local')
            : null;

        DocumentRequest::create([
            'document_id' => $document->document_id,
            'purpose' => $this->purpose === 'other'
                ? 'Other: ' . trim($this->otherPurpose)
                : $this->purposeOptions()[$this->purpose],
            'attachment_path' => $attachmentPath,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'date_of_request' => now()->toDateString(),
        ]);

        Notification::make()
            ->title('Request submitted successfully')
            ->body('The Legal Affairs Office will review your request.')
            ->success()
            ->send();

        $this->clearForm();
    }

    public function clearForm(): void
    {
        $this->reset([
            'laoNumber',
            'purpose',
            'otherPurpose',
            'attachment',
        ]);

        $this->resetValidation();
    }

    private function extractLaoNumber(string $payload): ?string
    {
        $candidates = [$payload];
        $decoded = json_decode($payload, true);

        if (is_array($decoded)) {
            foreach (['lao_number', 'laoNumber', 'lao'] as $key) {
                if (isset($decoded[$key]) && is_string($decoded[$key])) {
                    $candidates[] = $decoded[$key];
                }
            }
        }

        if (filter_var($payload, FILTER_VALIDATE_URL)) {
            $query = parse_url($payload, PHP_URL_QUERY);

            if (is_string($query)) {
                parse_str($query, $parameters);

                foreach (['lao_number', 'laoNumber', 'lao'] as $key) {
                    if (isset($parameters[$key])) {
                        $candidates[] = (string) $parameters[$key];
                    }
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (preg_match('/LAO-[A-Z0-9]+-\d+/i', $candidate, $matches)) {
                return strtoupper($matches[0]);
            }
        }

        return null;
    }

    private function extractDocumentId(string $payload): ?int
    {
        if (ctype_digit($payload)) {
            return (int) $payload;
        }

        if (! filter_var($payload, FILTER_VALIDATE_URL)) {
            return null;
        }

        $path = parse_url($payload, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        return preg_match(
            '#(?:^|/)(?:document-status|client/document-preview|client/document-download)/(\\d+)(?:/)?$#',
            trim($path, '/'),
            $matches
        ) === 1
            ? (int) $matches[1]
            : null;
    }
}
