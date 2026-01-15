<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Receta;
use App\Paciente;
use App\Doctor;
use DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use PDF;

class ControllerReceta extends Controller
{
    /**
     * Listar recetas de un paciente
     */
    public function listarRecetasPaciente($id_paciente)
    {
        try {
            $recetas = Receta::where('id_paciente', $id_paciente)
                ->with(['doctor', 'paciente'])
                ->orderBy('fecha', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json($recetas);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar recetas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una receta específica
     */
    public function obtenerReceta($id)
    {
        try {
            $receta = Receta::with(['doctor', 'paciente'])->findOrFail($id);
            return response()->json($receta);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener receta',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva receta
     */
    public function crearReceta(Request $request)
    {
        try {
            // Validar campos requeridos
            $request->validate([
                'id_paciente' => 'required|integer|exists:pacientes,id',
                'id_doctor' => 'required|integer|exists:doctors,id',
                'medicamentos' => 'required|array|min:1',
                'medicamentos.*.nombre' => 'required|string',
                'medicamentos.*.cantidad' => 'required|string',
                'medicamentos.*.dosis' => 'required|string',
                'medicamentos.*.frecuencia' => 'required|string',
                'medicamentos.*.duracion' => 'required|string',
                'indicaciones' => 'nullable|string',
                'diagnostico' => 'nullable|string',
                'fecha' => 'nullable|date'
            ]);

            // Verificar que el paciente existe
            $paciente = Paciente::find($request->id_paciente);
            if (!$paciente) {
                return response()->json([
                    'error' => 'Error al crear receta',
                    'message' => 'El paciente especificado no existe'
                ], 404);
            }

            // Verificar que el doctor existe
            $doctor = Doctor::find($request->id_doctor);
            if (!$doctor) {
                return response()->json([
                    'error' => 'Error al crear receta',
                    'message' => 'El doctor especificado no existe'
                ], 404);
            }

            // Generar código único de receta
            $ultimoId = Receta::max('id');
            $nuevoId = ($ultimoId ? $ultimoId + 1 : 1);
            $codigoReceta = 'REC-' . str_pad($nuevoId, 6, '0', STR_PAD_LEFT) . '-' . date('Y');

            // Preparar fecha
            $fecha = $request->fecha ? Carbon::parse($request->fecha) : Carbon::now();

            // Crear la receta
            $receta = new Receta();
            $receta->id_paciente = $request->id_paciente;
            $receta->id_doctor = $request->id_doctor;
            $receta->medicamentos = $request->medicamentos; // El mutator convertirá a JSON
            $receta->indicaciones = $request->indicaciones ?? null;
            $receta->diagnostico = $request->diagnostico ?? null;
            $receta->fecha = $fecha;
            $receta->codigo_receta = $codigoReceta;
            $receta->save();

            // Cargar relaciones
            $receta->load(['doctor', 'paciente']);

            return response()->json([
                'success' => true,
                'message' => 'Receta creada correctamente',
                'receta' => $receta
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => 'Por favor complete todos los campos requeridos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear receta: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al crear receta',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Actualizar una receta
     */
    public function actualizarReceta(Request $request, $id)
    {
        try {
            $receta = Receta::findOrFail($id);

            $request->validate([
                'medicamentos' => 'required|array|min:1',
                'medicamentos.*.nombre' => 'required|string',
                'medicamentos.*.cantidad' => 'required|string',
                'medicamentos.*.dosis' => 'required|string',
                'medicamentos.*.frecuencia' => 'required|string',
                'medicamentos.*.duracion' => 'required|string',
                'indicaciones' => 'nullable|string',
                'diagnostico' => 'nullable|string',
                'fecha' => 'nullable|date'
            ]);

            $receta->update([
                'medicamentos' => $request->medicamentos,
                'indicaciones' => $request->indicaciones,
                'diagnostico' => $request->diagnostico,
                'fecha' => $request->fecha ?? $receta->fecha
            ]);

            $receta->load(['doctor', 'paciente']);

            return response()->json([
                'success' => true,
                'message' => 'Receta actualizada correctamente',
                'receta' => $receta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar receta',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una receta
     */
    public function eliminarReceta($id)
    {
        try {
            $receta = Receta::findOrFail($id);
            $receta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Receta eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar receta',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Imprimir receta como PDF
     */
    public function imprimirReceta($id)
    {
        try {
            $receta = Receta::with(['doctor', 'paciente'])->findOrFail($id);
            
            // Obtener configuración de la clínica (si existe la tabla)
            $config = null;
            try {
                if (Schema::hasTable('configuracion')) {
                    $config = DB::table('configuracion')->first();
                }
            } catch (\Exception $e) {
                // Si no existe la tabla, usar valores por defecto
            }
            
            $data = [
                'receta' => $receta,
                'config' => $config,
                'fecha_impresion' => Carbon::now()->format('d/m/Y H:i')
            ];

            $pdf = PDF::loadView('receta_pdf', $data);
            
            return $pdf->download('receta-' . $receta->codigo_receta . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver receta en PDF (sin descargar)
     */
    public function verRecetaPDF($id)
    {
        try {
            $receta = Receta::with(['doctor', 'paciente'])->findOrFail($id);
            
            // Obtener configuración de la clínica (si existe la tabla)
            $config = null;
            try {
                if (Schema::hasTable('configuracion')) {
                    $config = DB::table('configuracion')->first();
                }
            } catch (\Exception $e) {
                // Si no existe la tabla, usar valores por defecto
            }
            
            $data = [
                'receta' => $receta,
                'config' => $config,
                'fecha_impresion' => Carbon::now()->format('d/m/Y H:i')
            ];

            $pdf = PDF::loadView('receta_pdf', $data);
            
            return $pdf->stream('receta-' . $receta->codigo_receta . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar receta por email
     */
    public function enviarRecetaEmail(Request $request, $id)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $receta = Receta::with(['doctor', 'paciente'])->findOrFail($id);
            
            // Obtener configuración de la clínica (si existe la tabla)
            $config = null;
            try {
                if (Schema::hasTable('configuracion')) {
                    $config = DB::table('configuracion')->first();
                }
            } catch (\Exception $e) {
                // Si no existe la tabla, usar valores por defecto
            }
            
            $data = [
                'receta' => $receta,
                'config' => $config,
                'fecha_impresion' => Carbon::now()->format('d/m/Y H:i')
            ];

            $pdf = PDF::loadView('receta_pdf', $data);
            
            // Enviar email con PDF adjunto
            \Mail::send('emails.receta', $data, function($message) use ($request, $receta, $pdf) {
                $message->to($request->email)
                        ->subject('Receta Médica - ' . $receta->codigo_receta)
                        ->attachData($pdf->output(), 'receta-' . $receta->codigo_receta . '.pdf');
            });

            return response()->json([
                'success' => true,
                'message' => 'Receta enviada por email correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al enviar receta',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
