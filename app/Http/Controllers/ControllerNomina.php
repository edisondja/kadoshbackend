<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PagoNomina;
use App\Procedimiento;
use App\Doctor;
use App\Empleado;
use App\Factura;
use App\Recibo;
use App\Historial_p;
use App\DoctorGananciaRecibo;
use DB;
use Carbon\Carbon;

class ControllerNomina extends Controller
{
    /**
     * Calcular nómina de doctores con comisiones por procedimientos
     * 
     * @param string $fecha_i Fecha inicial (formato: Y-m-d)
     * @param string $fecha_f Fecha final (formato: Y-m-d)
     * @return \Illuminate\Http\JsonResponse
     */
    public function calcularNominaDoctores($fecha_i = "", $fecha_f = "")
    {
        try {
            // Si no se proporcionan fechas, usar el mes actual
            if (empty($fecha_i) || empty($fecha_f)) {
                $fecha_i = Carbon::now()->startOfMonth()->format('Y-m-d');
                $fecha_f = Carbon::now()->endOfMonth()->format('Y-m-d');
            }

            $fecha_inicio = Carbon::parse($fecha_i)->startOfDay();
            $fecha_fin = Carbon::parse($fecha_f)->endOfDay();

            // Obtener todos los doctores
            $doctores = Doctor::all();
            $resultado = [];

            foreach ($doctores as $doctor) {
                // Obtener facturas del doctor en el período
                $facturas = Factura::where('id_doctor', $doctor->id)
                    ->whereHas('recibos', function($query) use ($fecha_inicio, $fecha_fin) {
                        $query->whereBetween('fecha_pago', [$fecha_inicio, $fecha_fin]);
                    })
                    ->with(['recibos' => function($query) use ($fecha_inicio, $fecha_fin) {
                        $query->whereBetween('fecha_pago', [$fecha_inicio, $fecha_fin]);
                    }])
                    ->get();

                $totalIngresos = 0;
                $totalComisiones = 0;
                $totalClinica = 0;
                $recibosGenerados = 0;
                $detalleProcedimientos = [];
                $totalGananciasManuales = 0; // Ganancias asignadas manualmente por recibo
                $totalGananciasClinicaManuales = 0;

                // Obtener ganancias asignadas manualmente por recibo en el período
                $gananciasManuales = DoctorGananciaRecibo::where('id_doctor', $doctor->id)
                    ->whereHas('recibo', function($query) use ($fecha_inicio, $fecha_fin) {
                        $query->whereBetween('fecha_pago', [$fecha_inicio, $fecha_fin]);
                    })
                    ->with('recibo')
                    ->get();

                // Obtener IDs de recibos con ganancias manuales para excluirlos del cálculo por procedimientos
                $recibosIdsConGanancia = $gananciasManuales->pluck('id_recibo')->toArray();
                
                foreach ($facturas as $factura) {
                    foreach ($factura->recibos as $recibo) {
                        if ($recibo->fecha_pago >= $fecha_inicio && $recibo->fecha_pago <= $fecha_fin) {
                            $recibosGenerados++;
                            $totalIngresos += $recibo->monto;

                            // Si el recibo tiene ganancia asignada manualmente, usar esos valores
                            if (in_array($recibo->id, $recibosIdsConGanancia)) {
                                $gananciaManual = $gananciasManuales->where('id_recibo', $recibo->id)->first();
                                if ($gananciaManual) {
                                    $totalGananciasManuales += $gananciaManual->ganancia_doctor;
                                    $totalGananciasClinicaManuales += $gananciaManual->ganancia_clinica;
                                }
                            } else {
                                // Si no tiene ganancia manual, calcular por procedimientos
                                // Obtener procedimientos del recibo
                                $procedimientosRecibo = json_decode($recibo->procedimientos, true);
                                
                                if (is_array($procedimientosRecibo)) {
                                    foreach ($procedimientosRecibo as $proc) {
                                        $procedimientoId = $proc['id'] ?? $proc['id_procedimiento'] ?? null;
                                        $cantidad = $proc['cantidad'] ?? 1;
                                        
                                        if ($procedimientoId) {
                                            $procedimiento = Procedimiento::find($procedimientoId);
                                            if ($procedimiento) {
                                                $comision = $procedimiento->calcularComision($cantidad);
                                                $totalComisiones += $comision;
                                                
                                                // Agregar al detalle
                                                if (!isset($detalleProcedimientos[$procedimientoId])) {
                                                    $detalleProcedimientos[$procedimientoId] = [
                                                        'nombre' => $procedimiento->nombre,
                                                        'cantidad' => 0,
                                                        'precio_unitario' => $procedimiento->precio,
                                                        'comision_porcentaje' => $procedimiento->comision ?? 0,
                                                        'total_comision' => 0
                                                    ];
                                                }
                                                $detalleProcedimientos[$procedimientoId]['cantidad'] += $cantidad;
                                                $detalleProcedimientos[$procedimientoId]['total_comision'] += $comision;
                                            }
                                        }
                                    }
                                }

                                // También buscar en historial_ps si existe
                                $historialProcedimientos = Historial_p::where('id_factura', $factura->id)->get();
                                foreach ($historialProcedimientos as $hist) {
                                    $procedimiento = Procedimiento::find($hist->id_procedimiento);
                                    if ($procedimiento) {
                                        $comision = $procedimiento->calcularComision($hist->cantidad);
                                        $totalComisiones += $comision;
                                        
                                        if (!isset($detalleProcedimientos[$hist->id_procedimiento])) {
                                            $detalleProcedimientos[$hist->id_procedimiento] = [
                                                'nombre' => $procedimiento->nombre,
                                                'cantidad' => 0,
                                                'precio_unitario' => $procedimiento->precio,
                                                'comision_porcentaje' => $procedimiento->comision ?? 0,
                                                'total_comision' => 0
                                            ];
                                        }
                                        $detalleProcedimientos[$hist->id_procedimiento]['cantidad'] += $hist->cantidad;
                                        $detalleProcedimientos[$hist->id_procedimiento]['total_comision'] += $comision;
                                    }
                                }
                            }
                        }
                    }
                }

                // Sumar ganancias manuales a las comisiones totales
                $totalComisiones += $totalGananciasManuales;
                // Calcular ganancias de la clínica: ingresos totales - ganancias del doctor (manuales + por procedimientos)
                $totalClinica = $totalIngresos - $totalComisiones;

                if ($totalIngresos > 0 || $recibosGenerados > 0) {
                    $resultado[] = [
                        'doctor_id' => $doctor->id,
                        'nombre' => $doctor->nombre,
                        'apellido' => $doctor->apellido ?? '',
                        'monto' => $totalIngresos,
                        'ganancias_doctor' => $totalComisiones,
                        'ganancias_clinica' => $totalClinica,
                        'recibos' => $recibosGenerados,
                        'detalle_procedimientos' => array_values($detalleProcedimientos)
                    ];
                }
            }

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular la nómina',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar un pago de nómina
     */
    public function registrarPagoNomina(Request $request)
    {
        try {
            $request->validate([
                'doctor_id' => 'nullable|exists:doctors,id',
                'empleado_id' => 'nullable|exists:empleados,id',
                'fecha_pago' => 'required|date',
                'periodo_inicio' => 'required|date',
                'periodo_fin' => 'required|date',
                'monto_comisiones' => 'required|numeric|min:0',
                'salario_base' => 'nullable|numeric|min:0',
                'comentarios' => 'nullable|string',
                'tipo' => 'nullable|in:comision,salario,mixto'
            ]);

            if (!$request->doctor_id && !$request->empleado_id) {
                return response()->json([
                    'error' => 'Debe especificar doctor_id o empleado_id'
                ], 400);
            }

            $totalPago = ($request->salario_base ?? 0) + $request->monto_comisiones;

            $pagoNomina = PagoNomina::create([
                'doctor_id' => $request->doctor_id,
                'empleado_id' => $request->empleado_id,
                'fecha_pago' => $request->fecha_pago,
                'periodo_inicio' => $request->periodo_inicio,
                'periodo_fin' => $request->periodo_fin,
                'monto_comisiones' => $request->monto_comisiones,
                'salario_base' => $request->salario_base ?? 0,
                'total_pago' => $totalPago,
                'estado' => $request->estado ?? 'pendiente',
                'comentarios' => $request->comentarios,
                'tipo' => $request->tipo ?? 'comision'
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Pago de nómina registrado correctamente',
                'pago' => $pagoNomina
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al registrar el pago de nómina',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar pagos de nómina
     */
    public function listarPagosNomina(Request $request)
    {
        try {
            $query = PagoNomina::with(['doctor', 'empleado']);

            if ($request->has('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }

            if ($request->has('empleado_id')) {
                $query->where('empleado_id', $request->empleado_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                $query->whereBetween('fecha_pago', [
                    $request->fecha_inicio,
                    $request->fecha_fin
                ]);
            }

            $pagos = $query->orderBy('fecha_pago', 'desc')->get();

            return response()->json($pagos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar los pagos de nómina',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar pago de nómina como pagado
     */
    public function marcarComoPagado($id)
    {
        try {
            $pago = PagoNomina::findOrFail($id);
            $pago->estado = 'pagado';
            $pago->fecha_pago = now();
            $pago->save();

            return response()->json([
                'status' => 'ok',
                'message' => 'Pago marcado como pagado',
                'pago' => $pago
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el pago',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalle de comisiones de un doctor en un período
     */
    public function obtenerDetalleComisiones($doctor_id, $fecha_i, $fecha_f)
    {
        try {
            $fecha_inicio = Carbon::parse($fecha_i)->startOfDay();
            $fecha_fin = Carbon::parse($fecha_f)->endOfDay();

            $facturas = Factura::where('id_doctor', $doctor_id)
                ->whereHas('recibos', function($query) use ($fecha_inicio, $fecha_fin) {
                    $query->whereBetween('fecha_pago', [$fecha_inicio, $fecha_fin]);
                })
                ->with(['recibos' => function($query) use ($fecha_inicio, $fecha_fin) {
                    $query->whereBetween('fecha_pago', [$fecha_inicio, $fecha_fin]);
                }])
                ->get();

            $detalle = [];
            foreach ($facturas as $factura) {
                foreach ($factura->recibos as $recibo) {
                    $procedimientosRecibo = json_decode($recibo->procedimientos, true);
                    if (is_array($procedimientosRecibo)) {
                        foreach ($procedimientosRecibo as $proc) {
                            $procedimientoId = $proc['id'] ?? $proc['id_procedimiento'] ?? null;
                            if ($procedimientoId) {
                                $procedimiento = Procedimiento::find($procedimientoId);
                                if ($procedimiento) {
                                    $detalle[] = [
                                        'fecha' => $recibo->fecha_pago,
                                        'procedimiento' => $procedimiento->nombre,
                                        'precio' => $procedimiento->precio,
                                        'comision_porcentaje' => $procedimiento->comision ?? 0,
                                        'comision_monto' => $procedimiento->calcularComision($proc['cantidad'] ?? 1),
                                        'cantidad' => $proc['cantidad'] ?? 1,
                                        'paciente' => $factura->paciente->nombre ?? ''
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            return response()->json($detalle);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el detalle',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
