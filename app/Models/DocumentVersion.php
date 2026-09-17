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
        'file_hash',
    ];

    public static function hashForUpload(mixed $file): ?string
    {
        $path = null;

        if (is_object($file) && method_exists($file, 'getRealPath')) {
            $path = $file->getRealPath();
        } elseif (is_string($file) && $file !== '') {
            foreach (['local', 'public'] as $diskName) {
                $disk = Storage::disk($diskName);

                if (! $disk->exists($file)) {
                    continue;
                }

                try {
                    $candidatePath = $disk->path($file);
                } catch (\Throwable) {
                    continue;
                }

                if (is_file($candidatePath)) {
                    $path = $candidatePath;

                    break;
                }
            }
        }

        if (! is_string($path) || ! is_file($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return is_string($hash) ? $hash : null;
    }

    public static function existsForDocumentOrUserHash(
        int $documentId,
        string $fileHash,
        ?int $userId = null,
    ): bool {
        return static::query()
            ->where('file_hash', $fileHash)
            ->where(function ($query) use ($documentId, $userId): void {
                $query->where('document_id', $documentId);

                if ($userId !== null) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->exists();
    }

    public static function removeUnreferencedUpload(mixed $file): void
    {
        if (! is_string($file) || $file === '') {
            return;
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if (! $disk->exists($file)) {
                continue;
            }

            if (
                ! static::query()->where('file_path', $file)->exists()
                && ! Document::query()->where('transmittal', $file)->exists()
            ) {
                $disk->delete($file);
            }

            return;
        }
    }

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
