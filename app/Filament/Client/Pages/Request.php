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

    public string $documentName = '';

    public string $laoNumber = '';

    public string $purpose = '';

    public $attachment = null;

    public string $qrValue = '';

    public function getHeading(): string
    {
        return '';
    }

    /**
     * Only documents owned by the signed-in client can be requested.
     * Documents need an LAO number because that is what the client enters/scans.
     */
    protected function requestableDocumentsQuery(): Builder
    {
        return Document::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('lao_number')
            ->whereIn('status', [
                'in_progress',
                'outgoing',
                'completed',
                'archived',
            ])
            ->orderBy('lao_number');
    }

    public function updatedLaoNumber(string $laoNumber): void
    {
        $document = $this->requestableDocumentsQuery()
            ->where('lao_number', trim($laoNumber))
            ->first();

        $this->documentName = $document?->particulars ?? '';
    }

    /**
     * Resolve QR payloads containing an LAO number, a URL with an LAO parameter,
     * JSON with an lao_number field, or a document ID as a last resort.
     */
    public function resolveQr(string $payload): void
    {
        $payload = trim($payload);
        $laoNumber = $this->extractLaoNumber($payload);

        $query = $this->requestableDocumentsQuery();

        $document = $laoNumber
            ? $query->where('lao_number', $laoNumber)->first()
            : null;

        if (! $document && ctype_digit($payload)) {
            $document = $this->requestableDocumentsQuery()
                ->whereKey((int) $payload)
                ->first();
        }

        if (! $document) {
            Notification::make()
                ->title('Document not found')
                ->body('The QR code does not match one of your documents with an LAO number.')
                ->danger()
                ->send();

            return;
        }

        $this->laoNumber = (string) $document->lao_number;
        $this->documentName = (string) $document->particulars;
        $this->qrValue = $payload;

        Notification::make()
            ->title('Document selected')
            ->body('LAO number ' . $document->lao_number . ' was found.')
            ->success()
            ->send();
    }

    public function submit(): void
    {
        $this->validate([
            'documentName' => ['required', 'string', 'max:255'],
            'laoNumber' => [
                'required',
                'string',
                'max:50',
                Rule::exists('documents', 'lao_number')
                    ->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
            'purpose' => ['required', 'string', 'max:2000'],
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
            $this->addError('laoNumber', 'The LAO number does not match one of your documents.');

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
            'purpose' => trim($this->purpose),
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
            'documentName',
            'laoNumber',
            'purpose',
            'attachment',
            'qrValue',
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
}
