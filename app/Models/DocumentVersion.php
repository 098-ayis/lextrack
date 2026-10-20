<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentVersion extends Model
{
    protected $primaryKey = 'version_id';

    /**
     * A new file version is also a document update. Touching the parent keeps
     * document lists ordered by the latest staff upload or edit.
     */
    protected $touches = ['document'];

    protected $fillable = [
        'user_id',
        'document_id',
        'version_number',
        'file_path',
        'file_disk',
        'original_filename',
        'file_hash',
        'source',
    ];

    public static function hashForUpload(mixed $file): ?string
    {
        if (is_object($file) && method_exists($file, 'getRealPath')) {
            $path = $file->getRealPath();

            return is_string($path) && is_file($path)
                ? hash_file('sha256', $path)
                : null;
        }

        if (! is_string($file) || $file === '') {
            return null;
        }

        $diskNames = array_unique([
            config('filesystems.document_disk', 'local'),
            'local',
            'public',
        ]);

        foreach ($diskNames as $diskName) {
            try {
                $disk = Storage::disk($diskName);

                if (! $disk->exists($file)) {
                    continue;
                }
            } catch (\Throwable) {
                continue;
            }

            try {
                $stream = $disk->readStream($file);
            } catch (\Throwable) {
                continue;
            }

            if (! is_resource($stream)) {
                return null;
            }

            try {
                $context = hash_init('sha256');
                hash_update_stream($context, $stream);

                return hash_final($context);
            } finally {
                fclose($stream);
            }
        }

        return null;
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

    public static function removeUnreferencedUpload(
        mixed $file,
        ?string $diskName = null
    ): void {
        if (! is_string($file) || $file === '') {
            return;
        }

        $diskName ??= config('filesystems.document_disk', 'local');

        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable) {
            return;
        }

        if (! $disk->exists($file)) {
            return;
        }

        // Never remove a path still referenced by any active attachment,
        // even when an older row recorded a different disk name.
        if (
            static::query()->where('file_path', $file)->exists()
            || MessageAttachment::query()->where('path', $file)->exists()
            || Document::query()->where('transmittal', $file)->exists()
            || DocumentTransmittal::query()->where('file_path', $file)->exists()
        ) {
            return;
        }

        $disk->delete($file);
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
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        )->withTrashed();
    }

    public function storageDisk()
    {
        return Storage::disk(
            $this->file_disk ?: 'local'
        );
    }
}
