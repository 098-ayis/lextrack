<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $primaryKey = 'note_id';

    protected $fillable = [
        'user_id',
        'document_id',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
