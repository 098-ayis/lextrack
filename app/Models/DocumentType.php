<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $primaryKey = 'type_id';

    protected $fillable = [
        'type_name',
        'type_desc',
        'color',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(
            Document::class,
            'type_id',
            'type_id'
        );
    }
}
