<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Receta;
use App\Paciente;
use App\Doctor;
use App\Config;
use DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

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
            // Determinar si se está usando texto libre (indicaciones con contenido)
            $usarTextoLibre = !empty($request->indicaciones) && trim($request->indicaciones) !== '';
            
            // Validar campos requeridos según el modo
            $rules = [
                'id_paciente' => 'required|integer|exists:pacientes,id',
                'id_doctor' => 'required|integer|exists:doctors,id',
                'indicaciones' => 'nullable|string',
                'diagnostico' => 'nullable|string',
                'fecha' => 'nullable|date'
            ];
            
            if ($usarTextoLibre) {
                // Si usa texto libre, los medicamentos son completamente opcionales
                // NO validar medicamentos en absoluto - pueden ser null, no estar presentes, o ser array vacío
                // No agregar reglas de validación para medicamentos
            } else {
                // Si no usa texto libre, los medicamentos son requeridos
                $rules['medicamentos'] = 'required|array|min:1';
                $rules['medicamentos.*.nombre'] = 'required|string';
                $rules['medicamentos.*.cantidad'] = 'required|string';
                $rules['medicamentos.*.dosis'] = 'required|string';
                $rules['medicamentos.*.frecuencia'] = 'required|string';
                $rules['medicamentos.*.duracion'] = 'required|string';
            }
            
            $request->validate($rules);
            
            // Validación adicional SOLO si NO usa texto libre
            if (!$usarTextoLibre) {
                // Si no usa texto libre, debe haber al menos un medicamento
                if (empty($request->medicamentos) || !is_array($request->medicamentos) || count($request->medicamentos) === 0) {
                    return response()->json([
                        'error' => 'Error de validación',
                        'message' => 'Debe agregar al menos un medicamento',
                        'errors' => ['medicamentos' => ['Debe agregar al menos un medicamento']]
                    ], 422);
                }
            }
            // Si usa texto libre, NO validar medicamentos en absoluto - pueden estar ausentes, ser null, o array vacío
            // El texto libre es una alternativa completa a los medicamentos individuales

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
            // Si usa texto libre, guardar null; si no, guardar los medicamentos
            if ($usarTextoLibre) {
                $receta->medicamentos = null; // Guardar null cuando se usa texto libre
            } else {
                $receta->medicamentos = $request->medicamentos ?? []; // Guardar medicamentos si no usa texto libre
            }
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

            // Determinar si se está usando texto libre (indicaciones con contenido)
            $usarTextoLibre = !empty($request->indicaciones) && trim($request->indicaciones) !== '';
            
            // Validar campos requeridos según el modo
            $rules = [
                'indicaciones' => 'nullable|string',
                'diagnostico' => 'nullable|string',
                'fecha' => 'nullable|date'
            ];
            
            if ($usarTextoLibre) {
                // Si usa texto libre, los medicamentos son completamente opcionales
                // NO validar medicamentos en absoluto - pueden ser null, no estar presentes, o ser array vacío
                // No agregar reglas de validación para medicamentos
            } else {
                // Si no usa texto libre, los medicamentos son requeridos
                $rules['medicamentos'] = 'required|array|min:1';
                $rules['medicamentos.*.nombre'] = 'required|string';
                $rules['medicamentos.*.cantidad'] = 'required|string';
                $rules['medicamentos.*.dosis'] = 'required|string';
                $rules['medicamentos.*.frecuencia'] = 'required|string';
                $rules['medicamentos.*.duracion'] = 'required|string';
            }
            
            $request->validate($rules);
            
            // Validación adicional SOLO si NO usa texto libre
            if (!$usarTextoLibre) {
                // Si no usa texto libre, debe haber al menos un medicamento
                if (empty($request->medicamentos) || !is_array($request->medicamentos) || count($request->medicamentos) === 0) {
                    return response()->json([
                        'error' => 'Error de validación',
                        'message' => 'Debe agregar al menos un medicamento',
                        'errors' => ['medicamentos' => ['Debe agregar al menos un medicamento']]
                    ], 422);
                }
            }
            // Si usa texto libre, NO validar medicamentos en absoluto - pueden estar ausentes, ser null, o array vacío
            // El texto libre es una alternativa completa a los medicamentos individuales
            
            // Preparar valor de medicamentos
            $medicamentosValue = null;
            if ($usarTextoLibre) {
                // Si usa texto libre, siempre guardar null
                $medicamentosValue = null;
            } else {
                // Si no usa texto libre, guardar los medicamentos
                $medicamentosValue = $request->medicamentos ?? [];
            }
            
            $receta->update([
                'medicamentos' => $medicamentosValue, // null si usa texto libre, array si no
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
            
            // Obtener configuración de la clínica
            $config = Config::first();
            
            // Convertir logo a base64 si existe
            $logoBase64 = null;
            if ($config && $config->ruta_logo) {
                // Intentar diferentes rutas posibles
                $possiblePaths = [
                    storage_path('app/public/' . $config->ruta_logo),
                    public_path('storage/' . $config->ruta_logo),
                    public_path($config->ruta_logo),
                    storage_path('app/' . $config->ruta_logo)
                ];
                
                $logoPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $logoPath = $path;
                        break;
                    }
                }
                
                if ($logoPath) {
                    try {
                        $logoData = file_get_contents($logoPath);
                        $logoInfo = pathinfo($logoPath);
                        $logoExtension = strtolower($logoInfo['extension'] ?? 'png');
                        $mimeType = 'image/' . ($logoExtension === 'jpg' ? 'jpeg' : $logoExtension);
                        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoData);
                    } catch (\Exception $e) {
                        \Log::warning('Error al leer logo: ' . $e->getMessage());
                    }
                } else {
                    \Log::warning('Logo no encontrado en ninguna ruta. Ruta en BD: ' . $config->ruta_logo);
                }
            }
            
            $data = [
                'receta' => $receta,
                'config' => $config,
                'logoBase64' => $logoBase64,
                'fecha_impresion' => Carbon::now()->format('d/m/Y H:i')
            ];

            // Renderizar la vista
            $html = view('receta_pdf', $data)->render();
            
            // Configurar opciones de DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            
            // Crear instancia de DomPDF
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            return $dompdf->stream('receta-' . $receta->codigo_receta . '.pdf', ['Attachment' => true]);
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de receta: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
            
            // Obtener configuración de la clínica
            $config = Config::first();
            
            // Log para debugging
            if (!$config) {
                \Log::warning('No se encontró configuración de la clínica');
            } else {
                \Log::info('Configuración encontrada: ' . ($config->nombre_clinica ?? $config->nombre ?? 'Sin nombre'));
                \Log::info('Ruta logo: ' . ($config->ruta_logo ?? 'Sin logo'));
            }
            
            // Convertir logo a base64 si existe
            $logoBase64 = null;
            if ($config && $config->ruta_logo) {
                // Intentar diferentes rutas posibles
                $possiblePaths = [
                    storage_path('app/public/' . $config->ruta_logo),
                    public_path('storage/' . $config->ruta_logo),
                    public_path($config->ruta_logo),
                    storage_path('app/' . $config->ruta_logo)
                ];
                
                $logoPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $logoPath = $path;
                        break;
                    }
                }
                
                if ($logoPath) {
                    try {
                        $logoData = file_get_contents($logoPath);
                        $logoInfo = pathinfo($logoPath);
                        $logoExtension = strtolower($logoInfo['extension'] ?? 'png');
                        $mimeType = 'image/' . ($logoExtension === 'jpg' ? 'jpeg' : $logoExtension);
                        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoData);
                    } catch (\Exception $e) {
                        \Log::warning('Error al leer logo: ' . $e->getMessage());
                    }
                } else {
                    \Log::warning('Logo no encontrado en ninguna ruta. Ruta en BD: ' . $config->ruta_logo);
                }
            }
            
            $data = [
                'receta' => $receta,
                'config' => $config,
                'logoBase64' => $logoBase64,
                'fecha_impresion' => Carbon::now()->format('d/m/Y H:i')
            ];

            // Renderizar la vista
            $html = view('receta_pdf', $data)->render();
            
            // Configurar opciones de DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            
            // Crear instancia de DomPDF
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            return $dompdf->stream('receta-' . $receta->codigo_receta . '.pdf', ['Attachment' => false]);
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de receta: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
            
            // Obtener configuración de la clínica
            $config = Config::first();
            
            // Convertir logo a base64 si existe
            $logoBase64 = null;
            if ($config && $config->ruta_logo) {
                // Intentar diferentes rutas posibles
                $possiblePaths = [
                    storage_path('app/public/' . $config->ruta_logo),
                    public_path('storage/' . $config->ruta_logo),
                    public_path($config->ruta_logo),
                    storage_path('app/' . $config->ruta_logo)
                ];
                
                $logoPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $logoPath = $path;
                        break;
                    }
                }
                
                if ($logoPath) {
                    try {
                        $logoData = file_get_contents($logoPath);
                        $logoInfo = pathinfo($logoPath);
                        $logoExtension = strtolower($logoInfo['extension'] ?? 'png');
                        $mimeType = 'image/' . ($logoExtension === 'jpg' ? 'jpeg' : $logoExtension);
                        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoData);
                    } catch (\Exception $e) {
                        \Log::warning('Error al leer logo: ' . $e->getMessage());
                    }
                } else {
                    \Log::warning('Logo no encontrado en ninguna ruta. Ruta en BD: ' . $config->ruta_logo);
                }
            }
            
            $data = [
                'receta' => $receta,
                'config' => $config,
                'logoBase64' => $logoBase64,
                'fecha_impresion' => Carbon::now()->format('d/m/Y H:i')
            ];

            // Renderizar la vista
            $html = view('receta_pdf', $data)->render();
            
            // Configurar opciones de DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            
            // Crear instancia de DomPDF
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Obtener el contenido del PDF
            $pdfOutput = $dompdf->output();
            
            // Enviar email con PDF adjunto
            \Mail::send('emails.receta', $data, function($message) use ($request, $receta, $pdfOutput) {
                $message->to($request->email)
                        ->subject('Receta Médica - ' . $receta->codigo_receta)
                        ->attachData($pdfOutput, 'receta-' . $receta->codigo_receta . '.pdf', [
                            'mime' => 'application/pdf',
                        ]);
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
