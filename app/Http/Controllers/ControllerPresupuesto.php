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

        $presupuesto = Presupuesto::with('paciente')
        ->where('paciente_id', $paciente_id)
        ->orderBy('id', 'desc')
        ->get();

        return $presupuesto;

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


    public function buscar_presupuesto($buscar){


        $presupuesto = Presupuesto::where("nombre","like","$buscar%")->get();
        
        return $presupuesto;


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

        $json_factura = json_encode($data->data);
        $prespuesto = new Presupuesto();
        $prespuesto->nombre =  $data->data["nombre"];
        $prespuesto->factura = $json_factura;
        $prespuesto->paciente_id = $data->data["id_paciente"];
        $prespuesto->doctor_id = $data->data["id_doctor"];
        $prespuesto->save();

        return $prespuesto;

    
    }

 
}
