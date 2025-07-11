<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Suplidor;
use DB;
use Carbon\Carbon;
use Factura;

class ControllerFinanciero extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //suplidors
    //Actualizado 6-05-2021


    public function suplidores(){

        return DB::table("suplidors")->get();
    }
    public function actualizar_suplidor(Request $suplidor){

        
        
            DB::table("suplidors")->updateOrInsert(
                ['id'=>$suplidor->input('id')],
                [
                'nombre'=>$suplidor->input('nombre'),
                'descripcion'=>$suplidor->input('descripcion'),
                'rnc_suplidor'=>$suplidor->input('rnc_suplidor')
                ]
            );

            return "Actualizado correctamente";
        
    
        
    }

    public function registrar_suplidor(Request $data){
        

            $sup = new Suplidor();
            $sup->nombre = $data->nombre;
            $sup->descripcion = $data->descripcion;
            $sup->rnc_suplidor = $data->rnc_suplidor;
            $sup->usuario_id = $data->id_usuario;
            $sup->fecha_registro_s =date('ymd');
            $sup->save();
             
            return "Suplidor Registrado con exito";    
    
    }


    public function eliminar_suplidor($id){


    
            DB::table("suplidors")->where("id",$id)->delete();
            return "Suplidor eliminado con exito";



    }
  
    public function buscar_suplidor($nombre){

        $data = App\Supplidor::where('like',"%$nombre%")->get();
        return $data;


    }
    //GASTOS
    public function registrar_gastos(Request $data){
        
         DB::table('gastos')->insert(
             ['tipo_de_gasto'=>$data->input('tipo_de_gasto'),
              'tipo_de_pago'=>$data->input('tipo_de_pago'),
              'suplidor_id'=>$data->input('suplidor_id'),
              'itebis'=>$data->input('itebis'),
              'total'=>$data->input('total'),
              'fecha_registro'=>date('ymd'),
              'descripcion'=>$data->input('descripcion'),
              'comprobante_fiscal'=>$data->input('comprobante_fiscal')
             ]);

            return "Gasto registrado con exito";

       


    }


    public function actualizar_gasto(Request $data){

            

            DB::table('gastos')->updateOrInsert(
            ['id'=>$data->id],
            [
            'tipo_de_gasto'=>$data->input('tipo_de_gasto'),
            'tipo_de_pago'=>$data->input('tipo_de_pago'),
            'suplidor_id'=>$data->input('suplidor_id'),
            'itebis'=>$data->input('itebis'),
            'total'=>$data->total,
            'fecha_registro'=>date('ymd'),
            'descripcion'=>$data->input('descripcion'),
            'comprobante_fiscal'=>$data->input('comprobante_fiscal')
           ]);
  
           return "Gasto actualizado con exito";
   
          


    }


    public function cargar_gasto($id){

        $data = DB::table('gastos')->where('id',$id)->get();
        return $data;

    }

    public function eliminar_gasto($id){


                DB::table('gastos')->where('id',$id)->delete();
                return "Registro eliminado con exito";

         


    }

    public function cargar_gastos(){

    
        $data= DB::table("gastos")->select("gastos.*","suplidors.nombre","suplidors.rnc_suplidor")->join("suplidors","gastos.suplidor_id","=","suplidors.id")->orderBy("gastos.id","desc")->limit(20)->get();
       
        return $data;

    }

    public function buscar_gasto($id){


        $data= DB::table("gastos")->select("gastos.*","suplidors.nombre","suplidors.rnc_suplidor")->join("suplidors","gastos.suplidor_id","=","suplidors.id")->where("gastos.id",$id)->orderBy("gastos.id","desc")->limit(20)->get();

        return $data;

    }

    public function buscar_por_fecha($fecha_i,$fecha_f){


           // return $fecha_i." ".$fecha_f;
            if($fecha_i=="" && $fecha_f==""){

                return DB::table("gastos")->join("suplidors","gastos.suplidor_id","=","suplidors.id")->where("fecha_registro",date('ymd'))->get();
                
            }else{  

                //$fecha_i." 00:00:00";
                $fecha_f." 23:59:59";

                return DB::table("gastos")->select("gastos.*","suplidors.nombre")->join("suplidors","gastos.suplidor_id","=","suplidors.id")->whereBetween("gastos.fecha_registro",array($fecha_i,$fecha_f))->get();

            }



    }


    public function procedimientos_realizados(){


            $data = DB::table("historial_ps")->count();

            return $data;


    }


    public function cargar_gastos_fecha($fecha_i,$fecha_f){

       //return  (string) Carbon::today()->isToday();
 

       if($fecha_i=="s" && $fecha_f=="s"){

            $fecha_i = date('y-m-d');
            $bruto = DB::table("gastos")->where("fecha_registro",$fecha_i)->sum('total');
            $itebis = DB::table("gastos")->where("fecha_registro",$fecha_i)->sum('itebis');

            return ($bruto+$itebis);
      
       }else{

      
            $sql = "SELECT sum(total) as monto from gastos  where fecha_registro BETWEEN '$fecha_i 12:00:00' AND '$fecha_f 23:30:00'";     
            $monto =DB::select(DB::raw($sql));
            
            $sql = "SELECT sum(itebis) as itebis from gastos  where fecha_registro BETWEEN '$fecha_i 12:00:00' AND '$fecha_f 23:30:00'";
            $itebis =DB::select(DB::raw($sql));

            return ($monto[0]->monto + $itebis[0]->itebis);
            /*recibos.fecha_pago 
            BETWEEN '$fecha_i 12:00:00' AND '$fecha_f 23:30:00'
            */
          

           
    

       }    
    
    
     
    }


    public function cargar_nomina($fecha_i="",$fecha_f=""){


      //  return date('yy-m-d');
        
        if($fecha_i=="s" && $fecha_f=="s"){
            

            //cargar nomina regenerada hoy
        
            
            $hoy = date('yy-m-d');
        

            $sql ="SELECT COUNT(*) as recibos,doctors.nombre,doctors.apellido,SUM(recibos.monto) 
            as monto FROM recibos inner JOIN facturas on recibos.id_factura=facturas.id
            inner JOIN doctors on doctors.id=facturas.id_doctor
            where recibos.id_factura=facturas.id and recibos.fecha_pago 
            BETWEEN '$hoy 12:00:00' AND '2021-05-03 23:30:00' GROUP BY doctors.nombre";
        
        }else{

            $sql ="SELECT COUNT(*) as recibos,doctors.nombre,doctors.apellido,SUM(recibos.monto) 
            as monto FROM recibos inner JOIN facturas on recibos.id_factura=facturas.id
            inner JOIN doctors on doctors.id=facturas.id_doctor
            where recibos.id_factura=facturas.id and recibos.fecha_pago 
            BETWEEN '$fecha_i 12:00:00' AND '$fecha_f 23:30:00' GROUP BY doctors.nombre";

        }
        
        $data =DB::select( DB::raw($sql) );
    
        return $data;


    }




}
