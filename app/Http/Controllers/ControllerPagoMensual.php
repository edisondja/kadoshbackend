<?php

namespace App\Http\Controllers;

use App\PagoMensual;
use App\Usuario;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ControllerPagoMensual extends Controller
{
    /**
     * Registrar un nuevo pago mensual
     */
    public function CrearPagoMensual(Request $request)
    {
        try {
            $request->validate([
                'usuario_id' => 'required|exists:usuarios,id',
                'fecha_pago' => 'required|date',
                'fecha_vencimiento' => 'required|date|after:fecha_pago',
                'monto' => 'required|numeric|min:0',
                'dias_gracia' => 'nullable|integer|min:0',
            ]);

            $pago = PagoMensual::create([
                'usuario_id' => $request->usuario_id,
                'fecha_pago' => $request->fecha_pago,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'monto' => $request->monto,
                'estado' => $request->estado ?? 'pendiente',
                'comentarios' => $request->comentarios ?? null,
                'dias_gracia' => $request->dias_gracia ?? 3,
            ]);

            return response()->json($pago, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el pago mensual',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar un pago como pagado
     */
    public function MarcarComoPagado($id)
    {
        try {
            $pago = PagoMensual::findOrFail($id);
            $pago->estado = 'pagado';
            $pago->fecha_pago = now();
            $pago->save();

            return response()->json($pago, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener pagos por usuario
     */
    public function ObtenerPagosPorUsuario($usuario_id)
    {
        try {
            $pagos = PagoMensual::where('usuario_id', $usuario_id)
                ->orderBy('fecha_vencimiento', 'desc')
                ->get();

            return response()->json($pagos, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener alertas de pagos próximos a vencer (dentro de días de gracia)
     */
    public function ObtenerAlertasPagos()
    {
        try {
            $hoy = Carbon::now();
            $alertas = [];

            $pagos = PagoMensual::where('estado', '!=', 'pagado')
                ->where('fecha_vencimiento', '>=', $hoy)
                ->with('usuario')
                ->get();

            foreach ($pagos as $pago) {
                $diasRestantes = $hoy->diffInDays($pago->fecha_vencimiento, false);
                $fechaAlerta = $pago->fecha_vencimiento->copy()->subDays($pago->dias_gracia);

                // Si estamos dentro del período de alerta (días de gracia antes del vencimiento)
                if ($hoy >= $fechaAlerta && $diasRestantes >= 0) {
                    $alertas[] = [
                        'id' => $pago->id,
                        'usuario' => $pago->usuario->nombre . ' ' . $pago->usuario->apellido,
                        'usuario_id' => $pago->usuario_id,
                        'fecha_vencimiento' => $pago->fecha_vencimiento->format('Y-m-d'),
                        'dias_restantes' => $diasRestantes,
                        'dias_gracia' => $pago->dias_gracia,
                        'monto' => $pago->monto,
                        'estado' => $pago->estado,
                    ];
                }
            }

            return response()->json($alertas, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las alertas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el próximo pago a vencer para un usuario específico
     */
    public function ObtenerProximoPagoUsuario($usuario_id)
    {
        try {
            $hoy = Carbon::now();
            
            $pago = PagoMensual::where('usuario_id', $usuario_id)
                ->where('estado', '!=', 'pagado')
                ->where('fecha_vencimiento', '>=', $hoy)
                ->orderBy('fecha_vencimiento', 'asc')
                ->first();

            if (!$pago) {
                // Retornar 200 con null en lugar de 404 para evitar errores en consola
                return response()->json([
                    'pago' => null,
                    'dias_restantes' => null,
                    'en_alerta' => false,
                    'fecha_vencimiento' => null,
                    'message' => 'No hay pagos pendientes'
                ], 200);
            }

            $diasRestantes = $hoy->diffInDays($pago->fecha_vencimiento, false);
            $fechaAlerta = $pago->fecha_vencimiento->copy()->subDays($pago->dias_gracia);
            $enAlerta = $hoy >= $fechaAlerta;

            return response()->json([
                'pago' => $pago,
                'dias_restantes' => $diasRestantes,
                'en_alerta' => $enAlerta,
                'fecha_vencimiento' => $pago->fecha_vencimiento->format('Y-m-d'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el próximo pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los pagos
     */
    public function ListarPagos()
    {
        try {
            $pagos = PagoMensual::with('usuario')
                ->orderBy('fecha_vencimiento', 'desc')
                ->get();

            return response()->json($pagos, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar los pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
