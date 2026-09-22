<?php

namespace App\Filament\Client\Pages;

use App\Models\DocumentRequest;
use App\Services\AdminDocumentNotificationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;

class Request extends Page
{
    protected static ?string $slug = 'request-document';

    protected static ?string $navigationLabel = 'Request';

    protected static ?string $title = 'Request A Document';

    protected string $view = 'filament.client.pages.request';

    public string $purpose = '';

    public string $purposeOther = '';

    public string $purposeDetails = '';

    public string $copyType = '';

    public function getHeading(): string
    {
        return '';
    }

    public function updatedPurpose(string $purpose): void
    {
        $this->resetValidation('purpose');

        if ($purpose !== 'other') {
            $this->purposeOther = '';
            $this->resetValidation('purposeOther');
        }
    }

    public function updatedPurposeOther(): void
    {
        $this->resetValidation('purposeOther');
    }

    public function clearPurpose(): void
    {
        $this->purpose = '';
        $this->purposeOther = '';
        $this->resetValidation(['purpose', 'purposeOther']);
    }

    public function updatedPurposeDetails(): void
    {
        $this->resetValidation('purposeDetails');
    }

    public function updatedCopyType(string $copyType): void
    {
        $this->resetValidation('copyType');
    }

    public function purposeOptions(): array
    {
        return [
            'certificate' => 'Certificate Request',
            'template' => 'Template Request',
            'document_request' => 'Document Request',
            'other' => 'Other Request',
        ];
    }

    public function copyTypeOptions(): array
    {
        return [
            'original' => 'Original copy (for pickup)',
            'soft_copy' => 'Soft copy (digital)',
        ];
    }

    public function submit(): void
    {
        $this->validate([
            'purpose' => [
                'required',
                Rule::in(array_keys($this->purposeOptions())),
            ],
            'purposeOther' => [
                'nullable',
                'required_if:purpose,other',
                'string',
                'max:255',
            ],
            'purposeDetails' => [
                'required',
                'string',
                'max:200',
            ],
            'copyType' => [
                'required',
                Rule::in(array_keys($this->copyTypeOptions())),
            ],
        ]);

        $purpose = $this->purpose === 'other'
            ? trim($this->purposeOther)
            : $this->purposeOptions()[$this->purpose];

        $request = DocumentRequest::create([
            'purpose' => $purpose,
            'purpose_details' => trim($this->purposeDetails),
            'copy_type' => $this->copyType,
            'pickup_at' => null,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'date_of_request' => now()->toDateString(),
        ]);

        app(AdminDocumentNotificationService::class)
            ->notifyRequestSubmitted($request);

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
            'purpose',
            'purposeOther',
            'purposeDetails',
            'copyType',
        ]);

        $this->resetValidation();
    }

}
