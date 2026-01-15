<?php

namespace App\Helpers;

use App\Log;

class AuditoriaHelper
{
    /**
     * Registrar una acción en el log de auditoría
     */
    public static function registrar($usuarioId, $modulo, $accion, $descripcion = null)
    {
        try {
            if ($usuarioId) {
                Log::crearLog(
                    $usuarioId,
                    $modulo,
                    $accion,
                    $descripcion
                );
            }
        } catch (\Exception $e) {
            // No fallar si no se puede crear el log
            \Log::warning('No se pudo crear log de auditoría: ' . $e->getMessage());
        }
    }
}
