<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Document extends Model
{
    protected $primaryKey = 'document_id';

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            if (
                blank($document->public_id)
                && Schema::hasColumn($document->getTable(), 'public_id')
            ) {
                $document->public_id = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Return the public route key, with a legacy primary-key fallback for
     * records created before the public_id migration was completed.
     */
    public function getPublicRouteKey(): string
    {
        return (string) ($this->public_id ?: $this->getKey());
    }

    /**
     * Resolve both the preferred public ULID and legacy numeric document IDs.
     */
    public static function findForRoute(string|int $identifier): self
    {
        $identifier = (string) $identifier;

        return static::query()
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('public_id', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhereKey((int) $identifier);
                }
            })
            ->firstOrFail();
    }
    
    protected $fillable = [
        'user_id',
        'document_type',
        'transmittal',
        'description',
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
        'sent_date' => 'date',
        'outgoing_date' => 'date',
        'date_returned' => 'date',
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

    /**
     * Generate the next LAO number for the year represented by the document's
     * upload date. Existing LAO numbers are used as the source of truth for
     * the next sequence value.
     */
    public static function generateLaoNumber(
        ?CarbonInterface $documentDate = null,
    ): string {
        $year = ($documentDate ?? now())->format('y');

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

    public function transmittalAttachments(): HasMany
    {
        return $this->hasMany(
            DocumentTransmittal::class,
            'document_id',
            'document_id'
        )->orderBy('transmittal_id');
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

    public function getDateAccomplishedAttribute(): ?\Carbon\CarbonInterface
    {
        $log = $this->activityLogs
            ->filter(function ($log) {
                if ($log->action_type === 'Document completed') {
                    return true;
                }

                $old = json_decode($log->old_value ?? '', true);
                $new = json_decode($log->new_value ?? '', true);

                return $log->action_type === 'Document updated'
                    && ($new['status'] ?? null) === 'completed'
                    && ($old['status'] ?? null) !== 'completed';
            })
            ->sortByDesc('created_at')->first();

        return $log?->created_at;
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

    

    public function notificationLabel(): string
    {
        return $this->document_name ?: $this->particulars ?: $this->lao_number ?: 'Untitled document';
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
            'outgoing' => 'border border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-800 dark:bg-violet-950 dark:text-violet-300',
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

    /**
     * Return version numbers uploaded through a client revision request.
     *
     * Client revisions use the same version source as the original client
     * submission, so the revision message distinguishes them in the viewer.
     */
    public function revisionVersionNumbers(): Collection
    {
        $messages = $this->conversation()
            ->with('messages')
            ->first()?->messages ?? collect();

        return $messages
            ->pluck('body')
            ->filter(fn (mixed $body): bool => preg_match(
                '/\bversion(?:s)?\s+(.+?)\s+and\s+(?:is|are)\s+ready\b/i',
                (string) $body
            ) === 1)
            ->flatMap(function (mixed $body): array {
                preg_match(
                    '/\bversion(?:s)?\s+(.+?)\s+and\s+(?:is|are)\s+ready\b/i',
                    (string) $body,
                    $matches,
                );

                preg_match_all('/\d+(?:\.\d+)?/', $matches[1] ?? '', $numbers);

                return $numbers[0] ?? [];
            })
            ->map(fn (string $version): string => trim($version))
            ->unique()
            ->values();
    }

    public function scopeAvailableForMessaging(Builder $query): Builder
    {
        return $query
            ->whereNotNull('lao_number')
            ->where('lao_number', '!=', '')
            ->whereNotIn('status', ['pending', 'rejected']);
    }

    public function isAvailableForMessaging(): bool
    {
        return filled($this->lao_number)
            && ! in_array($this->status, ['pending', 'rejected'], true);
    }

    public function hasClientRecipient(): bool
    {
        return $this->user?->hasRole('Client') ?? false;
    }

    public function messageDocument(int $documentId): void
    {
        $document = static::findOrFail($documentId);

        $this->redirect(
            route('filament.admin.pages.messages', [
                'document' => $document->public_id,
            ])
        );
    }
}
