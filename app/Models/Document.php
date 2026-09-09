<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $primaryKey = 'document_id';
    
    protected $fillable = [
        'user_id',
        'file_hash',
        'document_type',
        'action_type',
        'lao_number',
        'document_name',
        'office_unit',
        'particulars',
        'deadline',
        'sent_to',
        'sent_date',
        'returned_from',
        'date_returned',
        'outgoing_date',
        'status',
        'status_other',
        'rejection_reason',
        'archived_at',
    ];

    protected $casts = [
        'deadline' => 'date',
        'archived_at' => 'datetime',
    ];

    public static function deadlineForType(
        ?string $documentType,
        ?CarbonInterface $startsAt = null,
    ): ?string {
        if (blank($documentType)) {
            return null;
        }

        $daysToProcess = DocumentType::query()
            ->where('type_name', $documentType)
            ->value('days_to_process');

        if ($daysToProcess === null) {
            return null;
        }

        return ($startsAt ?? now())
            ->copy()
            ->addDays((int) $daysToProcess)
            ->toDateString();
    }

    public static function generateLaoNumber(
        ?CarbonInterface $generatedAt = null,
    ): string {
        $year = ($generatedAt ?? now())->format('y');

        $highestNumber = static::query()
            ->whereNotNull('lao_number')
            ->where('lao_number', 'like', "LAO-{$year}-%")
            ->pluck('lao_number')
            ->map(function (string $laoNumber): int {
                $parts = explode('-', $laoNumber);

                return isset($parts[2]) ? (int) $parts[2] : 0;
            })
            ->max() ?? 0;

        return sprintf('LAO-%s-%03d', $year, $highestNumber + 1);
    }


    public function notes(): HasMany
    {
        return $this->hasMany(
            Note::class,
            'document_id',
            'document_id'
        );
    }

    public function versions(): HasMany
    {
        return $this->hasMany(
            DocumentVersion::class,
            'document_id',
            'document_id'
        );
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(
            DocumentVersion::class,
            'document_id',
            'document_id'
        )->ofMany([
            'created_at' => 'max',
            'version_id' => 'max',
        ]);
    }

    /**
     * Backward-compatible virtual attribute while attachments are stored in
     * document_versions instead of the documents table.
     */
    public function getFilePathAttribute(): ?string
    {
        return $this->latestVersion?->file_path;
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class,
            'document_id',
            'document_id');
    }

    public function rejections(): HasMany
    {
        return $this->hasMany(
            RejectedDocument::class,
            'document_id',
            'document_id'
        );
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(
            DocumentRequest::class,
            'document_id',
            'document_id'
        );
    }

    

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $this->status ?? 'Unknown')),
        };
    }

    public function statusClasses(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-700',
            'in_progress' => 'bg-blue-100 text-blue-700',
            'completed' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class, 'document_id');
    }

    protected static function booted(): void
    {
        static::created(function (Document $document) {

        // Load the uploader
        $user = $document->user;

        // Only create a conversation for client uploads
        if ($user?->hasRole('Client')) {
            $document->conversation()->create();
        }
    
        });
    }

    public function messageDocument(int $documentId): void
    {
        $this->redirect(
            route('filament.admin.pages.messages', [
                'document' => $documentId,
            ])
        );
    }
}
