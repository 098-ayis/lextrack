<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionType extends Model
{
    protected $primaryKey = 'action_id';

    protected $fillable = [
        'action_name',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(
            Document::class,
            'action_id',
            'action_id'
        );
    }
}