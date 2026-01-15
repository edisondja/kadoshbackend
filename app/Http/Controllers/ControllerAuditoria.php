<?php

namespace App\Http\Controllers;

use App\Log;
use App\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControllerAuditoria extends Controller
{
    /**
     * Obtener todos los logs del usuario logueado
     */
    public function ObtenerLogsUsuario($usuario_id)
    {
        try {
            $logs = Log::where('usuario_id', $usuario_id)
                ->with('usuario')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($logs, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los logs (solo para administradores)
     */
    public function ObtenerTodosLogs(Request $request)
    {
        try {
            $query = Log::with('usuario')
                ->orderBy('created_at', 'desc');

            // Filtros opcionales
            if ($request->has('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->has('modulo')) {
                $query->where('modulo', $request->modulo);
            }

            if ($request->has('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $logs = $query->paginate($request->get('per_page', 50));

            return response()->json($logs, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de logs por módulo
     */
    public function ObtenerEstadisticasLogs($usuario_id = null)
    {
        try {
            $query = Log::select('modulo', DB::raw('count(*) as total'))
                ->groupBy('modulo');

            if ($usuario_id) {
                $query->where('usuario_id', $usuario_id);
            }

            $estadisticas = $query->get();

            return response()->json($estadisticas, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un log manualmente (para testing o acciones especiales)
     */
    public function CrearLog(Request $request)
    {
        try {
            $request->validate([
                'usuario_id' => 'required|exists:usuarios,id',
                'modulo' => 'required|string',
                'accion' => 'required|string',
                'descripcion' => 'nullable|string',
            ]);

            $log = Log::crearLog(
                $request->usuario_id,
                $request->modulo,
                $request->accion,
                $request->descripcion
            );

            return response()->json($log, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el log',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
