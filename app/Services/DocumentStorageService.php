<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentStorageService
{
    /**
     * Return the configured private disk used for newly uploaded documents.
     */
    public function diskName(): string
    {
        return (string) config('filesystems.document_disk', config('filesystems.default', 'local'));
    }

    public function disk(?string $diskName = null): FilesystemAdapter
    {
        return Storage::disk($diskName ?: $this->diskName());
    }

    /**
     * Resolve a stored path. A recorded disk is authoritative; probing other
     * disks is only allowed for legacy callers that have no disk metadata.
     */
    public function diskNameFor(string $path, ?string $preferredDisk = null): ?string
    {
        foreach ($this->candidateDiskNames($preferredDisk) as $diskName) {
            try {
                if ($this->disk($diskName)->exists($path)) {
                    return $diskName;
                }
            } catch (\Throwable) {
                // A not-yet-configured optional disk must not hide legacy files.
            }
        }

        return null;
    }

    public function exists(string $path, ?string $preferredDisk = null): bool
    {
        return $this->diskNameFor($path, $preferredDisk) !== null;
    }

    /**
     * Run an operation that needs a real local path.
     *
     * S3-compatible adapters do not provide a usable local path, so the
     * object is streamed to a private temporary file and removed afterwards.
     * Local files are passed through without copying or deleting them.
     */
    public function withLocalPath(
        string $path,
        callable $callback,
        ?string $preferredDisk = null,
    ): mixed {
        $diskName = $this->diskNameFor($path, $preferredDisk);

        if ($diskName === null) {
            throw new RuntimeException('The stored document is not available.');
        }

        $disk = $this->disk($diskName);
        $localPath = $this->localPath($disk, $path);

        if ($localPath !== null) {
            return $callback($localPath);
        }

        $directory = storage_path('app/private/temp-storage');
        File::ensureDirectoryExists($directory, 0700, true);

        $temporaryPath = tempnam($directory, 'document-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary document file.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $materializedPath = $extension !== ''
            ? $temporaryPath.'.'.strtolower($extension)
            : $temporaryPath;

        if ($materializedPath !== $temporaryPath && ! rename($temporaryPath, $materializedPath)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to prepare a temporary document file.');
        }

        $keepUntilSent = false;

        try {
            $source = $disk->readStream($path);
            $target = fopen($materializedPath, 'wb');

            if (! is_resource($source) || $target === false) {
                if (is_resource($source)) {
                    fclose($source);
                }

                throw new RuntimeException('Unable to read the stored document.');
            }

            try {
                if (stream_copy_to_stream($source, $target) === false) {
                    throw new RuntimeException('Unable to copy the stored document.');
                }
            } finally {
                fclose($source);
                fclose($target);
            }

            $response = $callback($materializedPath);

            if (
                $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
                && $response->getFile()->getPathname() === $materializedPath
            ) {
                $keepUntilSent = true;

                return $response->deleteFileAfterSend(true);
            }

            return $response;
        } finally {
            if (! $keepUntilSent) {
                @unlink($materializedPath);
            }
        }
    }

    /**
     * Return a response for a private file without relying on adapter::path().
     * Remote files are copied to a private temp file and deleted after send.
     */
    public function downloadResponse(
        string $path,
        string $fileName,
        ?string $mimeType = null,
        ?string $preferredDisk = null,
    )
    {
        $diskName = $this->diskNameFor($path, $preferredDisk);

        if ($diskName === null) {
            abort(404);
        }

        $disk = $this->disk($diskName);
        $localPath = $this->localPath($disk, $path);
        $headers = $mimeType ? ['Content-Type' => $mimeType] : [];

        if ($localPath !== null) {
            return response()->download($localPath, $fileName, $headers);
        }

        $temporaryDirectory = storage_path('app/private/temp-storage');
        File::ensureDirectoryExists($temporaryDirectory, 0700, true);
        $temporaryPath = tempnam($temporaryDirectory, 'download-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary download file.');
        }

        try {
            $source = $disk->readStream($path);
        } catch (\Throwable $exception) {
            @unlink($temporaryPath);

            throw $exception;
        }

        $target = fopen($temporaryPath, 'wb');

        if (! is_resource($source) || $target === false) {
            if (is_resource($source)) {
                fclose($source);
            }
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to read the stored document.');
        }

        try {
            if (stream_copy_to_stream($source, $target) === false) {
                throw new RuntimeException('Unable to copy the stored document.');
            }
        } finally {
            fclose($source);
            fclose($target);
        }

        return response()->download($temporaryPath, $fileName, $headers)
            ->deleteFileAfterSend(true);
    }

    /**
     * Hash a stored object without requiring a local adapter.
     */
    public function hash(string $path, ?string $preferredDisk = null): ?string
    {
        $diskName = $this->diskNameFor($path, $preferredDisk);

        if ($diskName === null) {
            return null;
        }

        $stream = $this->disk($diskName)->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $context = hash_init('sha256');

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 1024 * 1024);

                if ($chunk === false) {
                    return null;
                }

                if ($chunk !== '') {
                    hash_update($context, $chunk);
                }
            }

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }

    /** @return list<string> */
    public function candidateDiskNames(?string $preferredDisk = null): array
    {
        if (filled($preferredDisk)) {
            return [$preferredDisk];
        }

        return array_values(array_unique(array_filter([
            $this->diskName(),
            'local',
            'public',
        ], static fn (?string $diskName): bool => filled($diskName))));
    }

    private function localPath(FilesystemAdapter $disk, string $path): ?string
    {
        try {
            $localPath = $disk->path($path);
        } catch (\Throwable) {
            return null;
        }

        return is_string($localPath) && is_file($localPath) && is_readable($localPath)
            ? $localPath
            : null;
    }
}
