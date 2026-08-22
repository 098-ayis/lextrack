<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $primaryKey = 'type_id';
    
    protected $fillable = [
        'type_name',
        'type_desc',
        'created_at',
        'updated_at',
        'color',
    ];
}
