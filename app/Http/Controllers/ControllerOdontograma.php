<?php

namespace App\Http\Controllers;
use App\Odontograma;
use App\Odontograma_detalles;
use App\Log;
use App\Helpers\AuditoriaHelper;
use App\Doctor;
use App\Paciente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ControllerOdontograma extends Controller
{
    
    public function CrearOdontograma(Request $request)
    {
        try {
            // Validar que los campos requeridos estén presentes y sean válidos
            $idPaciente = $request->input('id_paciente');
            $idDoctor = $request->input('id_doctor');
            
            if (!$idPaciente || !$idDoctor) {
                return response()->json([
                    'message' => 'Error: id_paciente e id_doctor son requeridos'
                ], 400);
            }
            
            // Validar que los IDs sean números válidos y mayores a 0
            $idPaciente = (int) $idPaciente;
            $idDoctor = (int) $idDoctor;
            
            if ($idPaciente <= 0 || $idDoctor <= 0) {
                return response()->json([
                    'message' => 'Error: id_paciente e id_doctor deben ser números válidos mayores a 0'
                ], 400);
            }
            
            // Verificar que el doctor existe
            $doctor = Doctor::find($idDoctor);
            if (!$doctor) {
                return response()->json([
                    'message' => 'Error: El doctor con ID ' . $idDoctor . ' no existe'
                ], 404);
            }
            
            // Verificar que el paciente existe
            $paciente = Paciente::find($idPaciente);
            if (!$paciente) {
                return response()->json([
                    'message' => 'Error: El paciente con ID ' . $idPaciente . ' no existe'
                ], 404);
            }

            // Validar y limpiar el dibujo_odontograma
            $dibujoOdontograma = $request->dibujo_odontograma ?? $request->dibujoOdontograma ?? '';
            
            // Si el dibujo es muy grande, truncarlo (limitar a 10MB aproximadamente)
            if (strlen($dibujoOdontograma) > 10000000) {
                \Log::warning('Dibujo odontograma muy grande, truncando...');
                $dibujoOdontograma = substr($dibujoOdontograma, 0, 10000000);
            }

            $odontograma = Odontograma::create([
                'doctor_id' => $idDoctor,
                'paciente_id' => $idPaciente,
                'dibujo_odontograma' => $dibujoOdontograma,
                'estado' => 'activo',
            ]);

            // Si hay detalles (procedimientos), guardarlos
            if ($request->has('detalles') && is_array($request->detalles) && count($request->detalles) > 0) {
                foreach ($request->detalles as $detalle) {
                    Odontograma_detalles::create([
                        'odontograma_id' => $odontograma->id,
                        'diente' => $detalle['diente'] ?? '',
                        'cara' => $detalle['cara'] ?? null,
                        'tipo' => $detalle['tipo'] ?? 'procedimiento',
                        'descripcion' => $detalle['descripcion'] ?? $detalle['nombre'] ?? '',
                        'precio' => $detalle['precio'] ?? 0,
                        'color' => $detalle['color'] ?? null
                    ]);
                }
            }

            // Cargar los detalles para devolverlos en la respuesta
            $odontograma->load('detalles');
            
            // Registrar en auditoría
            $usuarioId = $request->input('usuario_id') ?? null;
            AuditoriaHelper::registrar(
                $usuarioId,
                'Odontogramas',
                'Crear Odontograma',
                "Odontograma #{$odontograma->id} creado para paciente #{$request->id_paciente} con " . count($request->detalles ?? []) . " procedimientos"
            );
            
            return response()->json($odontograma, 201);
        } catch (\Exception $e) {
            \Log::error('Error al guardar odontograma: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error al guardar el odontograma',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function EliminarOdontograma($id, Request $request = null)
    {
        $odontograma = Odontograma::find($id);
        if (!$odontograma) {
            return response()->json(['message' => 'Odontograma no encontrado'], 404);
        }

        // Registrar en auditoría antes de eliminar
        $usuarioId = $request ? ($request->input('usuario_id') ?? $request->query('usuario_id') ?? null) : null;
        
        AuditoriaHelper::registrar(
            $usuarioId,
            'Odontogramas',
            'Eliminar Odontograma',
            "Odontograma #{$id} eliminado del paciente #{$odontograma->paciente_id}"
        );

        $odontograma->delete();
        return response()->json(['message' => 'Odontograma eliminado'], 200);

    }

    public function VerOdontograma($id)
    {
        $odontograma = Odontograma::with('detalles')->find($id);
        if (!$odontograma) {
            return response()->json(['message' => 'Odontograma no encontrado'], 404);
        }

        return response()->json($odontograma, 200);
    }

    public function ListarOdontogramas()
    {
        $odontogramas = Odontograma::with('detalles')->get();
        return response()->json($odontogramas, 200);
    }

    public function ListarOdontogramasPorPaciente($id_paciente)
    {
        $odontogramas = Odontograma::where('paciente_id', $id_paciente)
            ->with('detalles')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($odontogramas, 200);
    }


}
