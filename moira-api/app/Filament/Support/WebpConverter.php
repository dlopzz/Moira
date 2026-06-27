<?php

namespace App\Filament\Support;

use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class WebpConverter
{
    public static function saveAs(string $directory, string $disk = 'public', int $quality = 85): Closure
    {
        return function (TemporaryUploadedFile $file) use ($directory, $disk, $quality): ?string {
            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (\Throwable) {
                return null;
            }

            $sourcePath = $file->getPathname();
            $imageData = file_get_contents($sourcePath);
            $image = @imagecreatefromstring($imageData);

            if ($image === false) {
                $ext = $file->guessExtension() ?? 'jpg';
                $path = $directory . '/' . Str::random(40) . '.' . $ext;
                Storage::disk($disk)->put($path, $imageData);
                rescue(fn () => Storage::disk($disk)->setVisibility($path, 'public'), report: false);
                return $path;
            }

            $filename = Str::random(40) . '.webp';
            $tmpPath = sys_get_temp_dir() . '/' . $filename;

            imagewebp($image, $tmpPath, $quality);
            imagedestroy($image);

            $storagePath = $directory . '/' . $filename;
            Storage::disk($disk)->put($storagePath, file_get_contents($tmpPath));
            rescue(fn () => Storage::disk($disk)->setVisibility($storagePath, 'public'), report: false);
            unlink($tmpPath);

            return $storagePath;
        };
    }
}
