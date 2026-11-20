<?php

namespace App\Services;

use App\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StorageQuotaService
{
    /** Cuota por defecto para todas las clínicas (admin puede cambiarla). */
    const DEFAULT_QUOTA_GB = 10.0;

    public function currentTenant(): ?Tenant
    {
        if (app()->bound('currentTenant')) {
            $t = app('currentTenant');
            if ($t instanceof Tenant) {
                return $t;
            }
        }
        return null;
    }

    public function resolveOrFallback(): ?Tenant
    {
        $tenant = $this->currentTenant();
        if ($tenant) {
            return $tenant;
        }

        try {
            $dbName = config('database.connections.mysql.database') ?: env('DB_DATABASE');
            if ($dbName) {
                return Tenant::where('database_name', $dbName)->first();
            }
        } catch (\Exception $e) {
            // ignore
        }

        return null;
    }

    /**
     * Disco de medios: Spaces si está configurado, si no public local.
     */
    public function mediaDiskName(): string
    {
        $configured = (string) config('filesystems.media', 'public');
        if ($configured === 'spaces') {
            $key = (string) config('filesystems.disks.spaces.key', '');
            $secret = (string) config('filesystems.disks.spaces.secret', '');
            if ($key === '' || $secret === '') {
                return 'public';
            }
            return 'spaces';
        }
        return $configured !== '' ? $configured : 'public';
    }

    public function mediaDisk()
    {
        return Storage::disk($this->mediaDiskName());
    }

    public function usesSpaces(): bool
    {
        return $this->mediaDiskName() === 'spaces';
    }

    public function storageKey(?Tenant $tenant = null): string
    {
        $tenant = $tenant ?: $this->resolveOrFallback();
        if ($tenant && !empty($tenant->subdominio)) {
            return strtolower(preg_replace('/[^a-z0-9\-]/', '', $tenant->subdominio));
        }
        return 'local';
    }

    public function tenantRoot(?Tenant $tenant = null): string
    {
        return 'tenants/' . $this->storageKey($tenant);
    }

    public function absoluteTenantPath(?Tenant $tenant = null): string
    {
        return storage_path('app/public/' . $this->tenantRoot($tenant));
    }

    /**
     * URL pública de un path relativo (Spaces o /storage/...).
     */
    public function publicUrl(?string $relativePath, ?Tenant $tenant = null): string
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return '';
        }

        $relativePath = trim(str_replace('\\', '/', $relativePath));
        if (preg_match('#^https?://#i', $relativePath)) {
            return $relativePath;
        }

        $relativePath = ltrim($relativePath, '/');
        if (strpos($relativePath, 'public/') === 0) {
            $relativePath = substr($relativePath, 7);
        }

        // Basename legacy → ruta tenant
        if (strpos($relativePath, '/') === false) {
            $resolved = $this->resolveRelativePath($relativePath, $tenant);
            if ($resolved) {
                $relativePath = $resolved;
            }
        }

        if ($this->usesSpaces()) {
            $onSpaces = false;
            try {
                $onSpaces = $this->mediaDisk()->exists($relativePath);
            } catch (\Throwable $e) {
                $onSpaces = false;
            }

            if ($onSpaces) {
                // Preferir URL pública del bucket; getObjectUrl de AWS falla con endpoint DO.
                $base = rtrim((string) config('filesystems.disks.spaces.url'), '/');
                if ($base === '') {
                    $base = rtrim((string) env('DO_SPACES_URL', ''), '/');
                }
                if ($base !== '') {
                    return $base . '/' . implode('/', array_map('rawurlencode', explode('/', $relativePath)));
                }
            }

            // Legacy local: servir vía proxy /storage/{archivo}
            if (strpos($relativePath, '/') === false) {
                return url('storage/' . rawurlencode($relativePath));
            }
            return asset('storage/' . $relativePath);
        }

        return asset('storage/' . $relativePath);
    }

    public function assertCanStore($bytes, ?Tenant $tenant = null): void
    {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return;
        }

        $tenant = $tenant ?: $this->resolveOrFallback();
        if (!$tenant) {
            return;
        }

        if (!$tenant->puedeAlmacenar($bytes)) {
            $available = max(0, $tenant->availableBytes());
            $needMb = round($bytes / 1048576, 2);
            $availMb = round($available / 1048576, 2);
            throw new HttpException(403, "Almacenamiento insuficiente. Necesita {$needMb} MB y quedan {$availMb} MB de {$tenant->storage_quota_gb} GB.");
        }
    }

    /**
     * Guarda un archivo bajo tenants/{slug}/[folder]/ y actualiza uso.
     * Comprime PNG/JPEG/PDF cuando es posible para reducir peso.
     * @return string ruta relativa al disco media
     */
    public function store(UploadedFile $file, $folder = '', ?Tenant $tenant = null): string
    {
        $tenant = $tenant ?: $this->resolveOrFallback();

        $compression = app(FileCompressionService::class)->compressUpload($file);
        /** @var UploadedFile $toStore */
        $toStore = $compression['file'];
        $bytes = (int) $compression['final_bytes'];
        if ($bytes <= 0) {
            $bytes = (int) $toStore->getSize();
        }

        $this->assertCanStore($bytes, $tenant);

        $base = $this->tenantRoot($tenant);
        $folder = trim(str_replace('\\', '/', (string) $folder), '/');
        $dir = $folder === '' ? $base : $base . '/' . $folder;

        $tmpPath = null;
        if (!empty($compression['compressed'])) {
            $tmpPath = $toStore->getRealPath();
        }

        try {
            $path = $toStore->store($dir, $this->mediaDiskName());
            $this->addUsage($bytes, $tenant);

            if (!empty($compression['compressed']) && $compression['original_bytes'] > $bytes) {
                \Log::info(sprintf(
                    'FileCompression saved %s→%s bytes (%s) tenant=%s',
                    $compression['original_bytes'],
                    $bytes,
                    $compression['note'],
                    $this->storageKey($tenant)
                ));
            }

            return $path;
        } finally {
            if ($tmpPath && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /** Igual que store(), pero retorna solo el basename (compatible con foto_paciente / radiografías). */
    public function storeBasename(UploadedFile $file, $folder = '', ?Tenant $tenant = null): string
    {
        return basename(str_replace('\\', '/', $this->store($file, $folder, $tenant)));
    }

    public function forget($relativePath, ?Tenant $tenant = null): void
    {
        if (!$relativePath || strpos((string) $relativePath, 'http') !== false) {
            return;
        }

        $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');
        if (strpos($relativePath, 'public/') === 0) {
            $relativePath = substr($relativePath, 7);
        }

        $tenant = $tenant ?: $this->resolveOrFallback();
        $size = 0;
        $diskName = $this->mediaDiskName();
        $disks = array_unique([$diskName, 'public']);

        foreach ($disks as $name) {
            $disk = Storage::disk($name);
            $targets = [$relativePath];
            if (strpos($relativePath, '/') === false) {
                $targets[] = $this->tenantRoot($tenant) . '/' . basename($relativePath);
                $targets[] = $this->tenantRoot($tenant) . '/radiografias/' . basename($relativePath);
                $targets[] = $this->tenantRoot($tenant) . '/configs/' . basename($relativePath);
                $targets[] = $this->tenantRoot($tenant) . '/temp_recibos/' . basename($relativePath);
            }

            foreach ($targets as $target) {
                if (!$disk->exists($target)) {
                    continue;
                }
                try {
                    $size = (int) $disk->size($target);
                } catch (\Exception $e) {
                    $size = 0;
                }
                $disk->delete($target);
                break 2;
            }
        }

        if ($size > 0) {
            $this->subtractUsage($size, $tenant);
        }
    }

    public function addUsage($bytes, ?Tenant $tenant = null): void
    {
        $tenant = $tenant ?: $this->resolveOrFallback();
        if (!$tenant || $bytes <= 0) {
            return;
        }
        try {
            DB::connection('mysql')->table('tenants')->where('id', $tenant->id)
                ->increment('storage_used_bytes', (int) $bytes);
            $tenant->storage_used_bytes = (int) $tenant->storage_used_bytes + (int) $bytes;
            if (app()->bound('currentTenant')) {
                app()->instance('currentTenant', $tenant);
            }
        } catch (\Exception $e) {
            \Log::warning('StorageQuota addUsage: ' . $e->getMessage());
        }
    }

    public function subtractUsage($bytes, ?Tenant $tenant = null): void
    {
        $tenant = $tenant ?: $this->resolveOrFallback();
        if (!$tenant || $bytes <= 0) {
            return;
        }
        try {
            $new = max(0, (int) $tenant->storage_used_bytes - (int) $bytes);
            DB::connection('mysql')->table('tenants')->where('id', $tenant->id)
                ->update(['storage_used_bytes' => $new]);
            $tenant->storage_used_bytes = $new;
            if (app()->bound('currentTenant')) {
                app()->instance('currentTenant', $tenant);
            }
        } catch (\Exception $e) {
            \Log::warning('StorageQuota subtractUsage: ' . $e->getMessage());
        }
    }

    public function recalculate(?Tenant $tenant = null): int
    {
        $tenant = $tenant ?: $this->resolveOrFallback();
        if (!$tenant) {
            return 0;
        }

        $total = 0;
        $prefix = $this->tenantRoot($tenant);

        try {
            $disk = $this->mediaDisk();
            foreach ($disk->allFiles($prefix) as $file) {
                try {
                    $total += (int) $disk->size($file);
                } catch (\Throwable $e) {
                    // ignore individual size errors
                }
            }
        } catch (\Throwable $e) {
            // Fallback local si Spaces falla
            $root = $this->absoluteTenantPath($tenant);
            if (is_dir($root)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $total += (int) $file->getSize();
                    }
                }
            }
        }

        Tenant::where('id', $tenant->id)->update(['storage_used_bytes' => $total]);
        $tenant->storage_used_bytes = $total;
        return $total;
    }

    public function snapshot(?Tenant $tenant = null): ?array
    {
        $tenant = $tenant ?: $this->resolveOrFallback();
        if (!$tenant) {
            return null;
        }

        $quotaGb = (float) ($tenant->storage_quota_gb ?? self::DEFAULT_QUOTA_GB);
        $used = (int) ($tenant->storage_used_bytes ?? 0);
        $quotaBytes = (int) round($quotaGb * 1024 * 1024 * 1024);
        $available = max(0, $quotaBytes - $used);
        $percent = $quotaBytes > 0 ? round(($used / $quotaBytes) * 100, 2) : 0;

        return [
            'quota_gb' => $quotaGb,
            'used_bytes' => $used,
            'available_bytes' => $available,
            'used_percent' => $percent,
            'quota_bytes' => $quotaBytes,
            'disk' => $this->mediaDiskName(),
        ];
    }

    /**
     * Resuelve path relativo en disco media (Spaces o public).
     */
    public function resolveRelativePath($archivo, ?Tenant $tenant = null): ?string
    {
        $archivo = str_replace('\\', '/', (string) $archivo);
        $archivo = ltrim($archivo, '/');
        if ($archivo === '' || strpos($archivo, '..') !== false) {
            return null;
        }

        $tenant = $tenant ?: $this->resolveOrFallback();
        $candidates = [$archivo];
        if (strpos($archivo, '/') === false) {
            $base = basename($archivo);
            $candidates[] = $this->tenantRoot($tenant) . '/' . $base;
            $candidates[] = $this->tenantRoot($tenant) . '/radiografias/' . $base;
            $candidates[] = $this->tenantRoot($tenant) . '/configs/' . $base;
            $candidates[] = $this->tenantRoot($tenant) . '/doctors/firmas/' . $base;
            $candidates[] = $this->tenantRoot($tenant) . '/temp_recibos/' . $base;
        } elseif (strpos($archivo, 'temp_recibos/') === 0) {
            $candidates[] = $this->tenantRoot($tenant) . '/' . $archivo;
        }

        $disks = array_unique([$this->mediaDiskName(), 'public']);
        foreach ($disks as $name) {
            $disk = Storage::disk($name);
            foreach ($candidates as $candidate) {
                try {
                    if ($disk->exists($candidate)) {
                        return $candidate;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Resuelve un archivo local (legacy). Null si solo está en Spaces.
     */
    public function resolvePublicFile($archivo): ?string
    {
        $rel = $this->resolveRelativePath($archivo);
        if ($rel === null) {
            return null;
        }

        // Solo paths locales
        $root = realpath(storage_path('app/public'));
        if ($root === false) {
            return null;
        }
        $full = storage_path('app/public/' . $rel);
        $real = realpath($full);
        if ($real !== false && strpos($real, $root) === 0 && is_file($real)) {
            return $real;
        }

        return null;
    }

    /**
     * Sirve o redirige un archivo (local o Spaces).
     */
    public function respondPublicFile($archivo)
    {
        $rel = $this->resolveRelativePath($archivo);
        if ($rel === null) {
            abort(404);
        }

        // Preferir URL directa de Spaces (CDN / bucket público)
        if ($this->usesSpaces()) {
            try {
                if ($this->mediaDisk()->exists($rel)) {
                    return redirect()->away($this->publicUrl($rel));
                }
            } catch (\Throwable $e) {
                // caer a local
            }
        }

        $local = $this->resolvePublicFile($rel);
        if ($local !== null) {
            return response()->file($local);
        }

        // Stream desde Spaces si el bucket no es público
        if ($this->usesSpaces()) {
            try {
                $disk = $this->mediaDisk();
                if ($disk->exists($rel)) {
                    $mime = $disk->mimeType($rel) ?: 'application/octet-stream';
                    return response($disk->get($rel), 200, [
                        'Content-Type' => $mime,
                        'Cache-Control' => 'public, max-age=86400',
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('Spaces serve file: ' . $e->getMessage());
            }
        }

        abort(404);
    }
}
