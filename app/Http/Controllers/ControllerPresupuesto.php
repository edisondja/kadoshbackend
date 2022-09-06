<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Presupuesto;
class ControllerPresupuesto extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function cargar_presupuestos($paciente_id){    


        $presupuesto = Presupuesto::where("paciente_id",$paciente_id)->orderBy("id","desc")->get();

        return $presupuesto;

    }


    public function buscar_presupuesto($buscar){


        $presupuesto = Presupuesto::where("nombre","like","$buscar%")->get();
        
        return $presupuesto;


    }

    public function cargar_presupuesto($id_presupuesto){

        $prespuesto = Presupuesto::find($id_presupuesto);
        $nombre = ["noombre"=>$prespuesto->nombre];
        return   $prespuesto->factura;

    }


    public function eliminar_prespuesto(Request $data){


        Presupuesto::where("id",$data->presupuesto_id)->delete();

        return "eliminado";

    }   


    public function actualizar_presupuesto(Request $data){


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
