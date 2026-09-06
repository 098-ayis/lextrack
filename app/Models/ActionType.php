<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionType extends Model
{
    protected $primaryKey = 'action_id';

    protected $fillable = [
        'action_name',
        'color',
    ];

}
