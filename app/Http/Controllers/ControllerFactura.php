<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App;
use App\Factura;

class ControllerFactura extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $data)
    {

       $total =  $data->input('total');
       //$cantidad = $data->input('cantidad');
       $id_doctor = $data->input('id_doctor');
       $id_paciente = $data->input('id_paciente');

       //return $total." ".$id_doctor." ".$id_paciente;
       $id_factura=DB::table("facturas")->insertGetId([
                'id_doctor'=>$id_doctor,
                'id_paciente'=>$id_paciente,
                'precio_estatus'=>$total

       ]);

      // return $id_factura;
       $id_factura= array("id_factura"=>$id_factura);

        //return $id_doctor;
       $procedimientos = $data->input('procedimientos');
       $procedimientosx = $procedimientos[0];
       $cantidad = count($procedimientosx);
       $array_new = [];
  
        for($i=0;$i<$cantidad;$i++){
        

                $array_new[] = [
                        'id_factura'=>$id_factura['id_factura'],
                        'total'=>$procedimientosx[$i]['total'],
                        'cantidad'=>$procedimientosx[$i]['cantidad'],
                        'id_procedimiento'=>$procedimientosx[$i]['id_procedimiento']
                ];
        }

        DB::table('historial_ps')->insert($array_new);
        return  $array_new;

        return "factura guardada con exito";


    
    }

    public function cargar_una_factura($id_factura){
        
            $factura = DB::table("facturas")->join("doctors","facturas.id_doctor","=","doctors.id")->where("facturas.id","=",$id_factura)->get();
            return $factura;
     }


    public function ConsultarProcedimientos($id){

        $persona = App\Procedimiento::find($id);
    
        return $persona;
    }

    public function cargar_facturas(){

            $data = DB::table('facturas')->get();
            return $data;
    }

    public function cargar_procedimientos_factura($id_factura){

        $data = DB::table('historial_ps')->join('procedimientos','historial_ps.id_procedimiento','=','procedimientos.id')->where('historial_ps.id_factura','=',$id_factura)->get();
        return $data;
    }

    public function cargar_recibos($id_factura){

           $data =  DB::table('recibos')->where('id_factura','=',$id_factura)->get();
           return $data;
    }

    public function EliminarProcedimiento($id){

        $procedimiento = App\Procedimiento::find($id);
        $procedimiento->delete();
        $procedimiento->save();
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public function Facturas_de_paciente($id_paciente){

        $data = DB::table("facturas")->where("id_paciente",$id_paciente)->get();
        return $data;

     }



}
