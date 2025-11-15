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

    return $presupuesto->factura;
}


    public function eliminar_prespuesto(Request $data){


        Presupuesto::where("id",$data->presupuesto_id)->delete();

        return "eliminado";

    }   

    public function actualizar_presupuesto(Request $data){


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
