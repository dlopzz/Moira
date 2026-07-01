<?php

namespace App\Filament\Support;

use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class WebpConverter
{
    /**
     * @param  array<string, int[]>  $variants  e.g. ['thumb' => [171, 171], 'medium' => [275, 367]]
     *   Each variant saves as "{slug}_{key}.webp" alongside the primary "{slug}.webp".
     *   The crop is always center-cover: fill the target dimensions from the center of the source.
     */
    public static function saveAs(
        string $directory,
        string $disk = 'public',
        int $quality = 85,
        array $variants = [],
    ): Closure {
        return function (TemporaryUploadedFile $file) use ($directory, $disk, $quality, $variants): ?string {
            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (\Throwable) {
                return null;
            }

            $imageData = file_get_contents($file->getPathname());
            $source    = @imagecreatefromstring($imageData);

            if ($source === false) {
                $ext  = $file->guessExtension() ?? 'jpg';
                $path = $directory . '/' . Str::random(40) . '.' . $ext;
                Storage::disk($disk)->put($path, $imageData);
                rescue(fn () => Storage::disk($disk)->setVisibility($path, 'public'), report: false);
                return $path;
            }

            $slug        = Str::random(40);
            $primaryName = $slug . '.webp';
            $primaryPath = $directory . '/' . $primaryName;

            // Save primary (original dimensions, WebP encoded)
            self::encodeAndStore($source, $primaryPath, $disk, $quality);

            // Save each variant with center-cover crop + resize
            foreach ($variants as $key => [$w, $h]) {
                $variant     = self::centerCover($source, $w, $h);
                $variantPath = $directory . '/' . $slug . '_' . $key . '.webp';
                self::encodeAndStore($variant, $variantPath, $disk, $quality);
                imagedestroy($variant);
            }

            imagedestroy($source);

            return $primaryPath;
        };
    }

    // ─────────────────────────────────────────────────────────────

    private static function centerCover(\GdImage $src, int $dstW, int $dstH): \GdImage
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);

        $srcAspect = $srcW / $srcH;
        $dstAspect = $dstW / $dstH;

        if ($srcAspect > $dstAspect) {
            // source is wider — crop left/right
            $cropH = $srcH;
            $cropW = (int) round($srcH * $dstAspect);
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            // source is taller — crop top/bottom
            $cropW = $srcW;
            $cropH = (int) round($srcW / $dstAspect);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        $dst = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $dstW, $dstH, $cropW, $cropH);

        return $dst;
    }

    private static function encodeAndStore(\GdImage $image, string $storagePath, string $disk, int $quality): void
    {
        $tmp = sys_get_temp_dir() . '/' . basename($storagePath);
        imagewebp($image, $tmp, $quality);
        Storage::disk($disk)->put($storagePath, file_get_contents($tmp));
        rescue(fn () => Storage::disk($disk)->setVisibility($storagePath, 'public'), report: false);
        unlink($tmp);
    }
}
