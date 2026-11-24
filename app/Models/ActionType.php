<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionType extends Model
{
    protected $primaryKey = 'action_id';

    protected $fillable = [
        'action_name',
        'color',
        'allows_custom_value',
    ];

    protected $casts = [
        'allows_custom_value' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'action_type_id', 'action_id');
    }
}
