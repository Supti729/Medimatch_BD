<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores a PUBLIC image (listing photo, profile picture) as a freshly encoded JPEG.
 *
 * Re-encoding removes ALL metadata. Phone photos often contain EXIF data with the exact GPS
 * position where they were taken, which would reveal a donor's home. It also removes anything
 * hidden in the file, and scales very large images down to MAX_SIDE pixels.
 *
 * Private documents (prescriptions) are NOT passed through here: reviewers need the original.
 */
class ImageSanitizer
{
    public const MAX_SIDE = 1600;

    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw new RuntimeException('The image could not be read.');
        }

        [$width, $height] = [imagesx($source), imagesy($source)];
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        [$newWidth, $newHeight] = [max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))];

        // Draw onto a white background (JPEG has no transparency), at the new size.
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = ob_get_clean();

        $path = trim($directory, '/').'/'.Str::random(40).'.jpg';
        Storage::disk($disk)->put($path, $jpeg);

        return $path;
    }
}
