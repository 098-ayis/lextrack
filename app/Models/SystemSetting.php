<?php

namespace AppModels;

use IlluminateDatabaseEloquentModel;

class SystemSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'value',
    ];
}
