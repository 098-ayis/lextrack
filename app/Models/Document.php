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

        static::saving(function (self $document): void {
            $document->synchronizeCatalogReferences();
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
        'document_type_id',
        'document_type',
        'description',
        'action_type_id',
        'action_type',
        'lao_number',
        'document_name',
        'office_unit_id',
        'office_unit',
        'particulars',
        'deadline',
        'sent_to',
        'sent_date',
        'returned_from',
        'date_returned',
        'return_remarks',
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

        $type = DocumentType::query()
            ->where('type_name', $documentType)
            ->first();

        if ($type === null && Schema::hasColumn('document_types', 'allows_custom_value')) {
            $type = DocumentType::query()
                ->where('allows_custom_value', true)
                ->orderBy('type_id')
                ->first();
        }

        $daysToProcess = $type?->days_to_process;

        if ($daysToProcess === null) {
            return null;
        }

        return ($startsAt ?? now())
            ->copy()
            ->addDays((int) $daysToProcess)
            ->toDateString();
    }

    public static function deadlineForTypeId(
        int|string|null $documentTypeId,
        ?CarbonInterface $startsAt = null,
    ): ?string {
        if (blank($documentTypeId)) {
            return null;
        }

        $daysToProcess = DocumentType::query()
            ->whereKey($documentTypeId)
            ->value('days_to_process');

        if ($daysToProcess === null) {
            return null;
        }

        return ($startsAt ?? now())
            ->copy()
            ->addDays((int) $daysToProcess)
            ->toDateString();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id', 'type_id');
    }

    public function officeUnit(): BelongsTo
    {
        return $this->belongsTo(OfficeUnit::class, 'office_unit_id', 'office_unit_id');
    }

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(ActionType::class, 'action_type_id', 'action_id');
    }

    public function getDocumentTypeLabelAttribute(): ?string
    {
        $catalog = $this->documentType;
        $snapshot = trim((string) $this->getRawOriginal('document_type'));

        if ($catalog?->allows_custom_value && $snapshot !== '') {
            return $snapshot;
        }

        return $catalog?->type_name ?: ($snapshot !== '' ? $snapshot : null);
    }

    public function getOfficeUnitLabelAttribute(): ?string
    {
        $catalog = $this->officeUnit;
        $snapshot = trim((string) $this->getRawOriginal('office_unit'));

        if ($catalog?->allows_custom_value && $snapshot !== '') {
            return $snapshot;
        }

        return $catalog?->name ?: ($snapshot !== '' ? $snapshot : null);
    }

    public function getActionTypeLabelAttribute(): ?string
    {
        $catalog = $this->actionType;
        $snapshot = trim((string) $this->getRawOriginal('action_type'));

        if ($catalog?->allows_custom_value && $snapshot !== '') {
            return $snapshot;
        }

        return $catalog?->action_name ?: ($snapshot !== '' ? $snapshot : null);
    }

    private function synchronizeCatalogReferences(): void
    {
        if (Schema::hasTable('document_types')
            && Schema::hasColumn($this->getTable(), 'document_type_id')) {
            $this->synchronizeDocumentType();
        }

        if (Schema::hasTable('office_units')
            && Schema::hasColumn($this->getTable(), 'office_unit_id')) {
            $this->synchronizeOfficeUnit();
        }

        if (Schema::hasTable('action_types')
            && Schema::hasColumn($this->getTable(), 'action_type_id')) {
            $this->synchronizeActionType();
        }
    }

    private function synchronizeDocumentType(): void
    {
        $snapshot = trim((string) $this->document_type);
        $catalog = filled($this->document_type_id)
            ? DocumentType::query()->find($this->document_type_id)
            : null;

        if ($catalog === null && $snapshot !== '') {
            $catalog = DocumentType::query()
                ->whereRaw('LOWER(TRIM(type_name)) = ?', [mb_strtolower($snapshot)])
                ->first();

            $catalog ??= DocumentType::query()
                ->where('allows_custom_value', true)
                ->orderBy('type_id')
                ->first();
        }

        if ($catalog === null) {
            return;
        }

        $this->document_type_id = $catalog->type_id;
        $this->document_type = $catalog->allows_custom_value && $snapshot !== ''
            ? $snapshot
            : $catalog->type_name;
        $this->setRelation('documentType', $catalog);
    }

    private function synchronizeOfficeUnit(): void
    {
        $snapshot = trim((string) $this->office_unit);
        $catalog = filled($this->office_unit_id)
            ? OfficeUnit::query()->find($this->office_unit_id)
            : null;

        if ($catalog === null && $snapshot !== '') {
            $catalog = OfficeUnit::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($snapshot)])
                ->first();

            $catalog ??= OfficeUnit::query()
                ->where('allows_custom_value', true)
                ->orderBy('office_unit_id')
                ->first();
        }

        if ($catalog === null) {
            return;
        }

        $this->office_unit_id = $catalog->office_unit_id;
        $this->office_unit = $catalog->allows_custom_value && $snapshot !== ''
            ? $snapshot
            : $catalog->name;
        $this->setRelation('officeUnit', $catalog);
    }

    private function synchronizeActionType(): void
    {
        $snapshot = trim((string) $this->action_type);
        if ($this->isDirty('action_type') && $snapshot === '') {
            $this->action_type_id = null;
            $this->unsetRelation('actionType');

            return;
        }

        $catalog = ! $this->isDirty('action_type') && filled($this->action_type_id)
            ? ActionType::query()->find($this->action_type_id)
            : null;

        if ($catalog === null && $snapshot !== '') {
            $catalog = ActionType::query()
                ->whereRaw('LOWER(TRIM(action_name)) = ?', [mb_strtolower($snapshot)])
                ->orderBy('action_id')
                ->first();

            $catalog ??= ActionType::query()
                ->where('allows_custom_value', true)
                ->orderBy('action_id')
                ->first();
        }

        if ($catalog === null) {
            return;
        }

        $this->action_type_id = $catalog->action_id;
        $this->action_type = $catalog->allows_custom_value && $snapshot !== ''
            ? $snapshot
            : $catalog->action_name;
        $this->setRelation('actionType', $catalog);
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

    public function clientViews(): HasMany
    {
        return $this->hasMany(DocumentView::class, 'document_id', 'document_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(DocumentView::class, 'document_id', 'document_id');
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
            'returned' => 'Returned',
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
            'returned' => 'border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300',
            'completed' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
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

    public function scopeForDocumentType(Builder $query, int|string|null $value): Builder
    {
        if (blank($value)) {
            return $query;
        }

        if (! Schema::hasTable('document_types')
            || ! Schema::hasColumn($this->getTable(), 'document_type_id')) {
            return $query->whereRaw(
                'LOWER(TRIM(document_type)) = ?',
                [mb_strtolower(trim((string) $value))],
            );
        }

        $type = is_numeric($value)
            ? DocumentType::query()->find((int) $value)
            : DocumentType::query()
                ->whereRaw('LOWER(TRIM(type_name)) = ?', [mb_strtolower(trim((string) $value))])
                ->first();

        return $query->where(function (Builder $query) use ($type, $value): void {
            if ($type) {
                $query->where('document_type_id', $type->type_id);
            }

            $method = $type ? 'orWhereRaw' : 'whereRaw';
            $query->{$method}(
                'LOWER(TRIM(document_type)) = ?',
                [mb_strtolower(trim((string) $value))],
            );
        });
    }

    public function scopeForOfficeUnit(Builder $query, int|string|null $value): Builder
    {
        if (blank($value)) {
            return $query;
        }

        if (! Schema::hasTable('office_units')
            || ! Schema::hasColumn($this->getTable(), 'office_unit_id')) {
            return $query->whereRaw(
                'LOWER(TRIM(office_unit)) = ?',
                [mb_strtolower(trim((string) $value))],
            );
        }

        $office = is_numeric($value)
            ? OfficeUnit::query()->find((int) $value)
            : OfficeUnit::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $value))])
                ->first();

        return $query->where(function (Builder $query) use ($office, $value): void {
            if ($office) {
                $query->where('office_unit_id', $office->office_unit_id);
            }

            $method = $office ? 'orWhereRaw' : 'whereRaw';
            $query->{$method}(
                'LOWER(TRIM(office_unit)) = ?',
                [mb_strtolower(trim((string) $value))],
            );
        });
    }

    public function scopeForActionType(Builder $query, int|string|null $value): Builder
    {
        if (blank($value)) {
            return $query;
        }

        if (! Schema::hasTable('action_types')
            || ! Schema::hasColumn($this->getTable(), 'action_type_id')) {
            return $query->whereRaw(
                'LOWER(TRIM(action_type)) = ?',
                [mb_strtolower(trim((string) $value))],
            );
        }

        $actionType = is_numeric($value)
            ? ActionType::query()->find((int) $value)
            : ActionType::query()
                ->whereRaw('LOWER(TRIM(action_name)) = ?', [mb_strtolower(trim((string) $value))])
                ->orderBy('action_id')
                ->first();

        return $query->where(function (Builder $query) use ($actionType, $value): void {
            if ($actionType) {
                $query->where('action_type_id', $actionType->action_id);
            }

            $method = $actionType ? 'orWhereRaw' : 'whereRaw';
            $query->{$method}(
                'LOWER(TRIM(action_type)) = ?',
                [mb_strtolower(trim((string) $value))],
            );
        });
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
