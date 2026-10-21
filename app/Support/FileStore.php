<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * All uploads live on the private disk (storage/app/private), under random UUID names.
 * They are never reachable by URL: the only way out is an authorised controller action.
 */
class FileStore
{
    public const DISK = 'private';

    private const SAFE_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** @return array{path:string, original_name:string, mime:?string, size:int, kind:string} */
    public static function store(UploadedFile $file, string $directory): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $size = (int) $file->getSize();
        $path = $file->storeAs($directory, Str::uuid()->toString().'.'.$ext, self::DISK);

        if ($path === false) {
            throw new \RuntimeException('Ֆայլը չհաջողվեց պահպանել։');
        }

        return [
            'path' => $path,
            'original_name' => self::cleanName($file->getClientOriginalName()),
            'mime' => $mime,
            'size' => $size,
            'kind' => in_array($ext, config('magical.uploads.image_extensions'), true) ? 'image' : 'file',
        ];
    }

    public static function deleteMany(array $paths): void
    {
        $paths = array_values(array_filter($paths));
        if ($paths !== []) {
            Storage::disk(self::DISK)->delete($paths);
        }
    }

    /** Streams a stored file. Everything is forced to download unless it is a known-safe image. */
    public static function response(string $path, string $name, ?string $mime, bool $inlineImage = false): Response
    {
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($path), 404, 'Ֆայլը չի գտնվել։');

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        if ($inlineImage && in_array($mime, self::SAFE_IMAGE_MIMES, true)) {
            return $disk->response($path, $name, $headers + ['Content-Type' => $mime], 'inline');
        }

        return $disk->download($path, $name, $headers + ['Content-Type' => 'application/octet-stream']);
    }

    private static function cleanName(string $name): string
    {
        $name = preg_replace('~^.*[\\\\/]~u', '', $name) ?? $name;
        $name = preg_replace('~[\x00-\x1F\x7F]+~u', '', $name) ?? $name;
        $name = trim(Str::limit($name, 200, ''));

        return $name !== '' ? $name : 'file';
    }
}
