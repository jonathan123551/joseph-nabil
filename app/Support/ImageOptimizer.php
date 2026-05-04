<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Resizes + re-encodes uploaded images before they hit Cloudinary so that:
 *   1. Phone cameras and high-DPI screenshots don't blow past PHP's
 *      post_max_size, and
 *   2. We pay less Cloudinary bandwidth + storage.
 *
 * Returns a path to a temporary optimized file. The caller is responsible
 * for unlinking it after upload completes.
 *
 * If anything goes wrong (HEIC, corrupt file, GD missing on a dev box, …)
 * we fall back to the original real path so the upload still goes through.
 * The goal is "best-effort optimization", never to break a working flow.
 */
class ImageOptimizer
{
    /**
     * @param  UploadedFile  $file
     * @param  int           $maxWidth  Cap the longest edge.
     * @param  int           $quality   JPEG quality 1-100 (recommended 75-85).
     */
    public static function optimize(UploadedFile $file, int $maxWidth = 1600, int $quality = 80): string
    {
        $originalPath = $file->getRealPath();

        try {
            $manager = new ImageManager(new Driver());
            $image   = $manager->read($originalPath);

            // scaleDown only resizes if the image is bigger than maxWidth on
            // either side, preserving aspect ratio. It's a no-op for already
            // small images (avoids upscaling).
            $image->scaleDown(width: $maxWidth);

            // Always re-encode as JPEG to get the compression win even on
            // already-small originals. PNG screenshots become smaller too.
            $encoded = $image->toJpeg(quality: $quality);

            $outPath = sys_get_temp_dir() . '/opt_' . uniqid('', true) . '.jpg';
            file_put_contents($outPath, (string) $encoded);

            return $outPath;
        } catch (Throwable $e) {
            // Best effort — upload the untouched file rather than 500.
            return $originalPath;
        }
    }
}
