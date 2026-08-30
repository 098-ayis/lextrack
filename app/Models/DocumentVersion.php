<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentVersion extends Model
{
    protected $primaryKey = 'version_id';

    protected $fillable = [
        'user_id',
        'document_id',
        'version_number',
        'file_path',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class,
            'document_id',
            'document_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storageDisk()
    {
        if (Storage::disk('local')->exists($this->file_path)) {
            return Storage::disk('local');
        }

        return Storage::disk('public');
    }
}
