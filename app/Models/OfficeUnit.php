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
        'allows_custom_value',
    ];

    protected $casts = [
        'allows_custom_value' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'office_unit_id', 'office_unit_id');
    }

}
