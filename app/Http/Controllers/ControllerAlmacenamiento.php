<?php

namespace App\Http\Controllers;

use App\Services\StorageQuotaService;
use Illuminate\Http\Request;

class ControllerAlmacenamiento extends Controller
{
    /**
     * Espacio de la clínica actual (para web y móvil).
     */
    public function estado(StorageQuotaService $quota)
    {
        $snap = $quota->snapshot();
        if (!$snap) {
            // Sin tenant resuelto: cuota por defecto abierta (entorno local)
            $quotaGb = StorageQuotaService::DEFAULT_QUOTA_GB;
            $quotaBytes = (int) round($quotaGb * 1024 * 1024 * 1024);
            return response()->json([
                'ok' => true,
                'disk' => $quota->mediaDiskName(),
                'uses_spaces' => $quota->usesSpaces(),
                'quota_gb' => $quotaGb,
                'used_bytes' => 0,
                'available_bytes' => $quotaBytes,
                'used_percent' => 0,
                'quota_bytes' => $quotaBytes,
                'used_label' => '0 B',
                'available_label' => $this->formatBytes($quotaBytes),
                'quota_label' => $quotaGb . ' GB',
            ]);
        }

        $spacesBase = '';
        if ($quota->usesSpaces()) {
            $spacesBase = rtrim((string) config('filesystems.disks.spaces.url'), '/');
        }

        return response()->json(array_merge([
            'ok' => true,
            'uses_spaces' => $quota->usesSpaces(),
            'storage_public_base' => $spacesBase !== '' ? $spacesBase : url('/storage'),
            'used_label' => $this->formatBytes($snap['used_bytes']),
            'available_label' => $this->formatBytes($snap['available_bytes']),
            'quota_label' => rtrim(rtrim(number_format($snap['quota_gb'], 2, '.', ''), '0'), '.') . ' GB',
        ], $snap));
    }

    private function formatBytes($bytes): string
    {
        $bytes = max(0, (int) $bytes);
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
