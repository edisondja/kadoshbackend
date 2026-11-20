<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Comprime PNG/JPEG/WebP (GD) y PDF (Ghostscript) antes de guardar.
 * Compatible con PHP 7.4 / Laravel 5.5.
 */
class FileCompressionService
{
    /** Ancho/alto máximo en px (mantiene proporción). */
    const MAX_IMAGE_EDGE = 1920;

    /** Calidad JPEG / WebP (1-100). */
    const JPEG_QUALITY = 78;

    /** Nivel PNG zlib (0-9). */
    const PNG_COMPRESSION = 8;

    /**
     * Devuelve un UploadedFile comprimido si se pudo reducir; si no, el original.
     *
     * @return array{file: UploadedFile, original_bytes: int, final_bytes: int, compressed: bool, note: string}
     */
    public function compressUpload(UploadedFile $file)
    {
        $originalBytes = (int) $file->getSize();
        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if (!$path || !is_file($path) || $originalBytes <= 0) {
            return $this->result($file, $originalBytes, $originalBytes, false, 'skip-empty');
        }

        // Evitar re-comprimir archivos ya pequeños
        if ($originalBytes < 40 * 1024 && strpos($mime, 'image/') === 0) {
            return $this->result($file, $originalBytes, $originalBytes, false, 'skip-small-image');
        }
        if ($originalBytes < 80 * 1024 && ($mime === 'application/pdf' || $ext === 'pdf')) {
            return $this->result($file, $originalBytes, $originalBytes, false, 'skip-small-pdf');
        }

        try {
            if ($this->isJpeg($mime, $ext) || $this->isPng($mime, $ext) || $this->isWebp($mime, $ext)) {
                $out = $this->compressImage($path, $mime, $ext);
            } elseif ($mime === 'application/pdf' || $ext === 'pdf') {
                $out = $this->compressPdf($path);
            } else {
                return $this->result($file, $originalBytes, $originalBytes, false, 'skip-type');
            }
        } catch (\Throwable $e) {
            \Log::warning('FileCompression: ' . $e->getMessage());
            return $this->result($file, $originalBytes, $originalBytes, false, 'error');
        }

        if ($out === null || !is_file($out['path'])) {
            return $this->result($file, $originalBytes, $originalBytes, false, 'no-output');
        }

        $finalBytes = (int) filesize($out['path']);
        // Solo usar si realmente ahorra (>= 5%)
        if ($finalBytes <= 0 || $finalBytes >= (int) ($originalBytes * 0.95)) {
            @unlink($out['path']);
            return $this->result($file, $originalBytes, $originalBytes, false, 'no-gain');
        }

        $compressed = $this->makeUploadedFile(
            $out['path'],
            $out['name'],
            $out['mime'],
            $finalBytes
        );

        return $this->result($compressed, $originalBytes, $finalBytes, true, $out['note']);
    }

    /**
     * Compatible con UploadedFile de Laravel 5.5 (con $size) y Symfony moderno (sin $size).
     */
    private function makeUploadedFile($path, $originalName, $mime, $size)
    {
        try {
            $ref = new \ReflectionMethod(UploadedFile::class, '__construct');
            $params = $ref->getNumberOfParameters();
            // L5.5 / Symfony < 4: (path, name, mime, size, error, test)
            if ($params >= 6) {
                return new UploadedFile($path, $originalName, $mime, $size, UPLOAD_ERR_OK, true);
            }
            // Symfony moderno: (path, name, mime, error, test)
            return new UploadedFile($path, $originalName, $mime, UPLOAD_ERR_OK, true);
        } catch (\Throwable $e) {
            return new UploadedFile($path, $originalName, $mime, UPLOAD_ERR_OK, true);
        }
    }

    private function result(UploadedFile $file, $original, $final, $compressed, $note)
    {
        return array(
            'file' => $file,
            'original_bytes' => (int) $original,
            'final_bytes' => (int) $final,
            'compressed' => (bool) $compressed,
            'note' => (string) $note,
        );
    }

    private function isJpeg($mime, $ext)
    {
        return $mime === 'image/jpeg' || $mime === 'image/jpg' || in_array($ext, array('jpg', 'jpeg'), true);
    }

    private function isPng($mime, $ext)
    {
        return $mime === 'image/png' || $ext === 'png';
    }

    private function isWebp($mime, $ext)
    {
        return $mime === 'image/webp' || $ext === 'webp';
    }

    /**
     * @return array{path:string,name:string,mime:string,note:string}|null
     */
    private function compressImage($srcPath, $mime, $ext)
    {
        if (!function_exists('imagecreatefromstring') || !extension_loaded('gd')) {
            return null;
        }

        $raw = @file_get_contents($srcPath);
        if ($raw === false || $raw === '') {
            return null;
        }

        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 1 || $h < 1) {
            imagedestroy($img);
            return null;
        }

        $max = self::MAX_IMAGE_EDGE;
        $scale = 1.0;
        $edge = max($w, $h);
        if ($edge > $max) {
            $scale = $max / $edge;
        }

        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        if ($scale < 1.0) {
            $dst = imagecreatetruecolor($nw, $nh);
            if ($this->isPng($mime, $ext) || $this->isWebp($mime, $ext)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
            }
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $dst;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'kadosh_img_');
        if ($tmp === false) {
            imagedestroy($img);
            return null;
        }

        $outName = pathinfo($srcPath, PATHINFO_FILENAME);
        $outMime = $mime;
        $outExt = $ext;
        $note = 'image';

        if ($this->isPng($mime, $ext)) {
            // PNG grandes → JPEG (mucho más liviano) si no necesitan transparencia real
            $hasAlpha = $this->pngHasMeaningfulAlpha($img);
            if (!$hasAlpha) {
                $jpgPath = $tmp . '.jpg';
                @unlink($tmp);
                imagejpeg($img, $jpgPath, self::JPEG_QUALITY);
                imagedestroy($img);
                return array(
                    'path' => $jpgPath,
                    'name' => $outName . '.jpg',
                    'mime' => 'image/jpeg',
                    'note' => 'png-to-jpeg',
                );
            }
            $pngPath = $tmp . '.png';
            @unlink($tmp);
            imagesavealpha($img, true);
            imagepng($img, $pngPath, self::PNG_COMPRESSION);
            imagedestroy($img);
            return array(
                'path' => $pngPath,
                'name' => $outName . '.png',
                'mime' => 'image/png',
                'note' => 'png',
            );
        }

        if ($this->isWebp($mime, $ext) && function_exists('imagewebp')) {
            $webpPath = $tmp . '.webp';
            @unlink($tmp);
            imagewebp($img, $webpPath, self::JPEG_QUALITY);
            imagedestroy($img);
            return array(
                'path' => $webpPath,
                'name' => $outName . '.webp',
                'mime' => 'image/webp',
                'note' => 'webp',
            );
        }

        // JPEG (default)
        $jpgPath = $tmp . '.jpg';
        @unlink($tmp);
        imagejpeg($img, $jpgPath, self::JPEG_QUALITY);
        imagedestroy($img);

        return array(
            'path' => $jpgPath,
            'name' => $outName . '.jpg',
            'mime' => 'image/jpeg',
            'note' => 'jpeg',
        );
    }

    private function pngHasMeaningfulAlpha($img)
    {
        if (!imageistruecolor($img)) {
            return false;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        // Sample grid — si algún pixel no es opaco, conservar PNG
        $stepX = max(1, (int) floor($w / 40));
        $stepY = max(1, (int) floor($h / 40));
        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                $rgba = imagecolorat($img, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return array{path:string,name:string,mime:string,note:string}|null
     */
    private function compressPdf($srcPath)
    {
        $gs = $this->ghostscriptBinary();
        if ($gs === null) {
            return null;
        }

        $originalSize = (int) filesize($srcPath);
        $profiles = array('ebook', 'screen');
        $bestPath = null;
        $bestSize = $originalSize;
        $bestProfile = null;

        foreach ($profiles as $profile) {
            $tmp = tempnam(sys_get_temp_dir(), 'kadosh_pdf_');
            if ($tmp === false) {
                continue;
            }
            $outPath = $tmp . '.pdf';
            @unlink($tmp);

            $cmd = escapeshellarg($gs)
                . ' -sDEVICE=pdfwrite'
                . ' -dCompatibilityLevel=1.4'
                . ' -dPDFSETTINGS=/' . $profile
                . ' -dNOPAUSE -dQUIET -dBATCH'
                . ' -dDetectDuplicateImages=true'
                . ' -dCompressFonts=true'
                . ' -dSubsetFonts=true'
                . ' -sOutputFile=' . escapeshellarg($outPath)
                . ' ' . escapeshellarg($srcPath)
                . ' 2>&1';

            $output = array();
            $code = 0;
            @exec($cmd, $output, $code);

            if ($code !== 0 || !is_file($outPath) || (int) filesize($outPath) < 64) {
                @unlink($outPath);
                continue;
            }

            $size = (int) filesize($outPath);
            if ($size > 0 && $size < $bestSize) {
                if ($bestPath && is_file($bestPath)) {
                    @unlink($bestPath);
                }
                $bestPath = $outPath;
                $bestSize = $size;
                $bestProfile = $profile;
            } else {
                @unlink($outPath);
            }
        }

        if ($bestPath === null) {
            return null;
        }

        $base = pathinfo($srcPath, PATHINFO_FILENAME);

        return array(
            'path' => $bestPath,
            'name' => $base . '.pdf',
            'mime' => 'application/pdf',
            'note' => 'pdf-gs-' . $bestProfile,
        );
    }

    private function ghostscriptBinary()
    {
        static $cached = false;
        static $bin = null;
        if ($cached) {
            return $bin;
        }
        $cached = true;

        $candidates = array('/usr/bin/gs', '/usr/local/bin/gs', 'gs');
        foreach ($candidates as $c) {
            if ($c === 'gs') {
                $which = trim((string) @shell_exec('command -v gs 2>/dev/null'));
                if ($which !== '' && is_executable($which)) {
                    $bin = $which;
                    return $bin;
                }
                continue;
            }
            if (is_executable($c)) {
                $bin = $c;
                return $bin;
            }
        }

        return null;
    }
}
