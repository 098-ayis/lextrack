<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeUnit extends Model
{
    protected $primaryKey = 'office_unit_id';

    protected $fillable = [
        'name',
        'color',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'office_unit_id');
    }
}
