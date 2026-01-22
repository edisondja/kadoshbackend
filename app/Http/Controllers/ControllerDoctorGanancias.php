<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DoctorGananciaRecibo;
use App\Recibo;
use App\Doctor;
use App\Factura;
use DB;
use Carbon\Carbon;

class ControllerDoctorGanancias extends Controller
{
    /**
     * Listar recibos con opción de asignar ganancias
     */
    public function listarRecibos(Request $request)
    {
        try {
            $fecha_i = $request->input('fecha_i');
            $fecha_f = $request->input('fecha_f');
            $doctor_id = $request->input('doctor_id');

            $query = Recibo::with(['factura.doctor', 'factura.paciente', 'doctorGanancias']);

            if ($fecha_i && $fecha_f) {
                $fecha_inicio = Carbon::parse($fecha_i)->startOfDay();
                $fecha_fin = Carbon::parse($fecha_f)->endOfDay();
                $query->whereBetween('fecha_pago', [$fecha_inicio, $fecha_fin]);
            }

            if ($doctor_id) {
                $query->whereHas('factura', function($q) use ($doctor_id) {
                    $q->where('id_doctor', $doctor_id);
                });
            }

            $recibos = $query->orderBy('fecha_pago', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $resultado = [];
            foreach ($recibos as $recibo) {
                $ganancia = $recibo->doctorGanancias->first();
                $resultado[] = [
                    'id' => $recibo->id,
                    'codigo_recibo' => $recibo->codigo_recibo,
                    'monto' => $recibo->monto,
                    'fecha_pago' => $recibo->fecha_pago,
                    'tipo_de_pago' => $recibo->tipo_de_pago,
                    'doctor' => [
                        'id' => $recibo->factura->doctor->id ?? null,
                        'nombre' => $recibo->factura->doctor->nombre ?? '',
                        'apellido' => $recibo->factura->doctor->apellido ?? ''
                    ],
                    'paciente' => [
                        'id' => $recibo->factura->paciente->id ?? null,
                        'nombre' => $recibo->factura->paciente->nombre ?? '',
                        'apellido' => $recibo->factura->paciente->apellido ?? ''
                    ],
                    'ganancia_id' => $ganancia ? $ganancia->id : null,
                    'ganancia_doctor' => $ganancia ? $ganancia->ganancia_doctor : null,
                    'ganancia_clinica' => $ganancia ? $ganancia->ganancia_clinica : null,
                    'observaciones' => $ganancia ? $ganancia->observaciones : null,
                    'tiene_ganancia_asignada' => $ganancia ? true : false
                ];
            }

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar recibos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar ganancia a un doctor por recibo
     */
    public function asignarGanancia(Request $request)
    {
        try {
            $request->validate([
                'id_recibo' => 'required|exists:recibos,id',
                'id_doctor' => 'required|exists:doctors,id',
                'ganancia_doctor' => 'required|numeric|min:0',
                'ganancia_clinica' => 'nullable|numeric|min:0',
                'observaciones' => 'nullable|string'
            ]);

            // Verificar que el recibo pertenece al doctor
            $recibo = Recibo::with('factura')->findOrFail($request->id_recibo);
            if ($recibo->factura->id_doctor != $request->id_doctor) {
                return response()->json([
                    'error' => 'El recibo no pertenece al doctor especificado'
                ], 400);
            }

            // Verificar que la suma de ganancias no exceda el monto del recibo
            $montoRecibo = $recibo->monto;
            $gananciaDoctor = $request->ganancia_doctor;
            $gananciaClinica = $request->ganancia_clinica ?? ($montoRecibo - $gananciaDoctor);

            if (($gananciaDoctor + $gananciaClinica) > $montoRecibo) {
                return response()->json([
                    'error' => 'La suma de ganancias no puede exceder el monto del recibo'
                ], 400);
            }

            // Crear o actualizar la ganancia
            $ganancia = DoctorGananciaRecibo::updateOrCreate(
                [
                    'id_recibo' => $request->id_recibo,
                    'id_doctor' => $request->id_doctor
                ],
                [
                    'ganancia_doctor' => $gananciaDoctor,
                    'ganancia_clinica' => $gananciaClinica,
                    'observaciones' => $request->observaciones
                ]
            );

            return response()->json([
                'status' => 'ok',
                'message' => 'Ganancia asignada correctamente',
                'ganancia' => $ganancia
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al asignar ganancia',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar ganancia asignada
     */
    public function eliminarGanancia($id)
    {
        try {
            $ganancia = DoctorGananciaRecibo::findOrFail($id);
            $ganancia->delete();

            return response()->json([
                'status' => 'ok',
                'message' => 'Ganancia eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar ganancia',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ganancias de un doctor en un período
     */
    public function obtenerGananciasDoctor($doctor_id, $fecha_i, $fecha_f)
    {
        try {
            $fecha_inicio = Carbon::parse($fecha_i)->startOfDay();
            $fecha_fin = Carbon::parse($fecha_f)->endOfDay();

            $ganancias = DoctorGananciaRecibo::where('id_doctor', $doctor_id)
                ->whereHas('recibo', function($query) use ($fecha_inicio, $fecha_fin) {
                    $query->whereBetween('fecha_pago', [$fecha_inicio, $fecha_fin]);
                })
                ->with(['recibo.factura.paciente'])
                ->get();

            $totalGananciaDoctor = $ganancias->sum('ganancia_doctor');
            $totalGananciaClinica = $ganancias->sum('ganancia_clinica');

            return response()->json([
                'ganancias' => $ganancias,
                'total_ganancia_doctor' => $totalGananciaDoctor,
                'total_ganancia_clinica' => $totalGananciaClinica,
                'total_ingresos' => $totalGananciaDoctor + $totalGananciaClinica
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener ganancias',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
