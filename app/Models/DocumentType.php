<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DocumentType extends Model
{
    public function scopeOrderedForChoices(Builder $query): Builder
    {
        return $query->orderByRaw("CASE WHEN LOWER(TRIM(type_name)) = 'others' THEN 1 ELSE 0 END")
            ->orderBy('type_name');
    }

    protected $primaryKey = 'type_id';

    protected $fillable = [
        'type_name',
        'type_desc',
        'color',
        'days_to_process',
    ];

    protected $casts = [
        'days_to_process' => 'integer',
    ];

}
