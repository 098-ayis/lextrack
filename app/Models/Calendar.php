<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $primaryKey = 'sched_id';

    protected $fillable = [
        'user_id',
        'date',
        'time',
        'event',
        'details',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
    ];

    public function user(): BelongsTo
    {
         return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }
}
