<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeUnit extends Model
{
    protected $primaryKey = 'office_unit_id';

    protected $fillable = [
        'name',
        'color',
    ];

}
