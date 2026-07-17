<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App;
use App\Presupuesto;
use Mail;
use App\Mail\ReciboMailable;
use App\Mail\PresupuestoMail;

class ControllerPresupuesto extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function cargar_presupuestos($paciente_id){    

        $presupuestos = Presupuesto::with(['paciente', 'doctor'])
            ->where('paciente_id', $paciente_id)
            ->orderBy('id', 'desc')
            ->get();

        // Asegurar que cada presupuesto incluya el doctor (cargar si falta y exponer nombre)
        $presupuestos->each(function ($presupuesto) {
            if (!$presupuesto->relationLoaded('doctor') && $presupuesto->doctor_id) {
                $presupuesto->load('doctor');
            }
            if ($presupuesto->doctor === null && $presupuesto->doctor_id) {
                $presupuesto->setRelation('doctor', \App\Doctor::find($presupuesto->doctor_id));
            }
        });

        return response()->json($presupuestos);
    }

    /**
     * Listar todos los presupuestos (no filtrados por paciente)
     */
    public function listar_todos_presupuestos(){    
        try {
            $presupuestos = Presupuesto::with('paciente')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($presupuestos);
        } catch (\Exception $e) {
            \Log::error('Error al listar todos los presupuestos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al listar presupuestos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Presupuestos de consulta (sin paciente registrado — estimación de precios).
     */
    public function listar_presupuestos_consulta()
    {
        try {
            $presupuestos = Presupuesto::orderBy('id', 'desc')
                ->get()
                ->filter(function ($presupuesto) {
                    if ($presupuesto->paciente_id === null || $presupuesto->paciente_id === '' || (int) $presupuesto->paciente_id === 0) {
                        return true;
                    }
                    $factura = json_decode($presupuesto->factura, true);
                    return is_array($factura) && ($factura['tipo'] ?? '') === 'consulta';
                })
                ->values()
                ->map(function ($presupuesto) {
                    $factura = json_decode($presupuesto->factura, true);
                    if (!is_array($factura)) {
                        $factura = [];
                    }
                    $presupuesto->tipo = $factura['tipo'] ?? 'consulta';
                    $presupuesto->cliente_nombre = $factura['cliente_nombre'] ?? $presupuesto->nombre;
                    $presupuesto->cliente_telefono = $factura['cliente_telefono'] ?? '';
                    $presupuesto->cliente_correo = $factura['cliente_correo'] ?? '';
                    $total = $factura['total'] ?? 0;
                    if (!$total && !empty($factura['procedimientos']) && is_array($factura['procedimientos'])) {
                        foreach ($factura['procedimientos'] as $proc) {
                            $total += (float) ($proc['total'] ?? 0);
                        }
                    }
                    $presupuesto->total = $total;
                    return $presupuesto;
                });

            return response()->json($presupuestos);
        } catch (\Exception $e) {
            \Log::error('Error al listar presupuestos de consulta: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al listar estimaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function buscar_presupuesto($buscar){


        $presupuestos = Presupuesto::with(['paciente', 'doctor'])->where("nombre","like","$buscar%")->get();

        $presupuestos->each(function ($presupuesto) {
            if ($presupuesto->doctor === null && $presupuesto->doctor_id) {
                $presupuesto->setRelation('doctor', \App\Doctor::find($presupuesto->doctor_id));
            }
        });

        return response()->json($presupuestos);


    }

public function cargar_presupuesto($id_presupuesto)
{
    $presupuesto = Presupuesto::with('paciente')
        ->where('id', $id_presupuesto)
        ->first();

    if (!$presupuesto) {
        return response()->json(['error' => 'Presupuesto no encontrado'], 404);
    }

    // Parsear la factura (procedimientos) y devolver el objeto completo
    $factura = json_decode($presupuesto->factura, true);
    
    // Si factura es null o no es un array, intentar parsear de nuevo
    if (!is_array($factura)) {
        $factura = [];
    }
    
    return response()->json([
        'id' => $presupuesto->id,
        'nombre' => $presupuesto->nombre,
        'paciente_id' => $presupuesto->paciente_id,
        'doctor_id' => $presupuesto->doctor_id,
        'tipo' => $factura['tipo'] ?? ($presupuesto->paciente_id ? 'paciente' : 'consulta'),
        'cliente_nombre' => $factura['cliente_nombre'] ?? '',
        'cliente_telefono' => $factura['cliente_telefono'] ?? '',
        'cliente_correo' => $factura['cliente_correo'] ?? '',
        'total' => $factura['total'] ?? ($presupuesto->total ?? 0),
        'procedimientos' => $factura['procedimientos'] ?? ($factura['lista_procedimiento'] ?? []),
        'factura' => $presupuesto->factura, // Mantener factura original para compatibilidad
        'created_at' => $presupuesto->created_at ? $presupuesto->created_at->toDateTimeString() : null,
        'updated_at' => $presupuesto->updated_at ? $presupuesto->updated_at->toDateTimeString() : null,
        'fecha' => $presupuesto->created_at ? $presupuesto->created_at->format('Y-m-d H:i:s') : null
    ]);
}


    public function eliminar_prespuesto(Request $data){


        Presupuesto::where("id",$data->presupuesto_id)->delete();

        return "eliminado";

    }   

    public function actualizar_presupuesto(Request $data){
        try {
            // Validar que el presupuesto existe
            $presupuesto = Presupuesto::find($data->presupuesto_id);
            
            if (!$presupuesto) {
                return response()->json([
                    'message' => 'Error: Presupuesto no encontrado'
                ], 404);
            }

            // Validar que los datos requeridos estén presentes
            if (!$data->has('data')) {
                return response()->json([
                    'message' => 'Error: Los datos del presupuesto son requeridos'
                ], 400);
            }

            $datosPresupuesto = $data->data;
            
            // Actualizar el presupuesto
            $json_factura = json_encode($datosPresupuesto);
            $presupuesto->nombre = $datosPresupuesto["nombre"] ?? $presupuesto->nombre;
            $presupuesto->factura = $json_factura;
            
            // Actualizar doctor_id si viene en los datos
            if (isset($datosPresupuesto["id_doctor"])) {
                $presupuesto->doctor_id = $datosPresupuesto["id_doctor"];
            }
            
            $presupuesto->save();

            return response()->json([
                'message' => 'Presupuesto actualizado correctamente',
                'presupuesto' => $presupuesto
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar presupuesto: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar el presupuesto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        public function enviarPresupuesto(Request $request)
        {
            \Log::info("Request recibido", [
                "all" => $request->all(),
                "files" => $request->files,
            ]);

            try {
                $validated = $request->validate([
                    "pdf" => "required|file|mimes:pdf",
                    "email" => "required|email",
                    "asunto" => "required|string",
                    "nombre_compania" => "nullable|string",
                    "logo_compania" => "nullable|string",
                    "direccion_compania" => "nullable|string",
                    "telefono_compania" => "nullable|string",
                    
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json($e->errors(), 422);
            }

            // Guardar temporalmente el archivo
            $pdfPath = $request->file("pdf")->store("presupuestos_temp");


     // Enviar correo
            // Enviar correo correctamente
            Mail::to($validated["email"])->send(new \App\Mail\PresupuestoMail(
                    $validated["asunto"],
                    $validated["nombre_compania"],
                    $validated["logo_compania"],
                    $validated["direccion_compania"],
                    $validated["telefono_compania"],
                    storage_path("app/" . $pdfPath)
                ));

            return response()->json([
                "status" => "success",
                "message" => "Presupuesto enviado correctamente"
            ], 200);
        }




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $data)
    {
        try {
            if (!$data->has('data')) {
                return response()->json(['message' => 'Datos del presupuesto requeridos'], 400);
            }

            $datos = $data->data;
            if (empty($datos['nombre'])) {
                return response()->json(['message' => 'El nombre del presupuesto es requerido'], 400);
            }
            if (empty($datos['id_doctor'])) {
                return response()->json(['message' => 'Debe seleccionar un doctor'], 400);
            }
            if (empty($datos['procedimientos']) || !is_array($datos['procedimientos'])) {
                return response()->json(['message' => 'Debe agregar al menos un procedimiento'], 400);
            }

            $json_factura = json_encode($datos);
            $prespuesto = new Presupuesto();
            $prespuesto->nombre = $datos['nombre'] ?? 'Presupuesto';
            $prespuesto->factura = $json_factura;

            $esConsulta = ($datos['tipo'] ?? '') === 'consulta';
            $pacienteId = null;
            if (!$esConsulta && !empty($datos['id_paciente'])) {
                $pacienteId = (int) $datos['id_paciente'];
            }
            $prespuesto->paciente_id = $pacienteId;
            $prespuesto->doctor_id = $datos['id_doctor'] ?? null;
            $prespuesto->save();

            return response()->json($prespuesto, 201);
        } catch (\Exception $e) {
            \Log::error('Error al crear presupuesto: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear el presupuesto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

 
}
