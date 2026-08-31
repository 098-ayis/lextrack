<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $primaryKey = 'document_id';
    
    protected $fillable = [
        'user_id',
        'type_id',
        'other_document_type',
        'action_id',
        'lao_number',
        'office_unit',
        'particulars',
        'deadline',
        'action_taken',
        'sent_to',
        'sent_date',
        'returned_from',
        'date_returned',
        'outgoing_date',
        'status',
        'status_other',
        'rejection_reason',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(
            ActionType::class,
            'action_id',
            'action_id'
        );
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

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'type_id', 'type_id');
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
