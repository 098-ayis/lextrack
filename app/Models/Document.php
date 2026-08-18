<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    protected $primaryKey = 'document_id';

    protected $fillable = [
        'name',
        'content',

        // Incoming document fields
        'lao_number',
        'type_id',
        'client_id',
        'office_unit',
        'particulars',
        'status',
        'status_other',
        'deadline',

        // Existing document fields
        'sent_to',
        'sent_date',
        'returned_from',
        'date_returned',
        'outgoing_date',
        'uploaded_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'sent_date' => 'date',
        'date_returned' => 'date',
        'outgoing_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(
            DocumentType::class,
            'type_id',
            'type_id'
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            Client::class,
            'client_id',
            'client_id'
        );
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'returned' => 'Returned',
            'archived' => 'Archived',
            'others' => $this->status_other ?: 'Others',
            default => ucfirst(
                str_replace('_', ' ', $this->status ?? 'Unknown')
            ),
        };
    }

    public function statusClasses(): string
    {
        return match ($this->status) {
            'pending' =>
                'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-400',

            'in_progress' =>
                'bg-info-50 text-info-700 dark:bg-info-400/10 dark:text-info-400',

            'completed' =>
                'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400',

            'returned' =>
                'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400',

            'archived' =>
                'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',

            'others' =>
                'bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400',

            default =>
                'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::created(function (Document $document) {
            $document->conversation()->create([
                'user_id' => $document->uploaded_by,
            ]);
        });
    }
}