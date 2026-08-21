<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    protected $primaryKey = 'document_id';

    protected $fillable = [
        'user_id',
        'type_id',
        'action_id',
        'lao_number',
        'office_unit',
        'particulars',
        'deadline',
        'sent_to',
        'sent_date',
        'returned_from',
        'date_returned',
        'outgoing_date',
        'status',
        'file_path',
    ];

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(
            ActionType::class,
            'action_id',
            'action_id'
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


    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    protected static function booted(): void
    {
        static::created(function (Document $document) {
            $document->conversation()->create();
        });
    }


}
