<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use DB;
use Carbon\Carbon;

class ControllerFinanciero extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //Suplidores


    public function suplidores(){

        return DB::table("suplidores")->take(20)->get();
    }
    public function actualizar_suplidor(Request $suplidor){

        
        
            DB::table("suplidores")->updateOrInsert(
                ['id'=>$suplidor->id],
                [
                'nombre'=>$suplidor->nombre,
                'descripcion'=>$suplidor->descripcion,
                'rnc_suplidor'=>$suplidor->rnc_suplidor,
                'fecha_registro_s'=>$this->fecha_registro,
                ]
            );

            return "Actualizado correctamente";
        
    
        
    }

    public function registrar_suplidor(Request $data){
        

        try{
            DB::table("suplidores")->insert([
                'nombre'=>$data->nombre,
                'descripcion'=>$data->descripcion,
                'rnc_suplidor'=>$data->rnc_suplidor,
                'fecha_registro_s'=>date('ymdiis')
            ]);
            return "Suplidor Registrado con exito";

        }
        catch(Exception $e){


            return "no se pudo guardar carajo!!!!";

        }   

    
    
    }


    public function eliminar_suplidor(Request $id){


        try{
            DB::table("suplidores")->where("id",$id->id)->delete();
            return "Suplidor eliminado con exito";

        }catch(Exception $e){

            return "Erro al eliminar suplidor";
        }

    }
  
    public function buscar_suplidor($nombre){

        $data = App\Supplidor::where('like',"%$nombre%")->take(20)->get();
        return $data;


    }
    //GASTOS
    public function registrar_gastos(Request $data){
        
         DB::table('gastos')->insert(
             ['tipo_de_gasto'=>$data->tipo_de_gasto,
              'tipo_de_pago'=>$data->tipo_de_pago,
              'suplidor_id'=>$data->suplidor_id,
              'itebis'=>$data->itebis,
              'total'=>$data->total,
              'fecha_registro'=>date('ymdiis'),
              'descripcion'=>$data->descripcion,
              'comprobante_fiscal'=>$data->comprobante_fiscal
             ]);

            return "Gasto registrado con exito";

       


    }


    public function actualizar_gasto(Request $data){

        try{
            

            DB::table('gastos')->updateOrInsert(
            ['id'=>$data->id],
            [
            'tipo_de_gasto'=>$data->tipo_de_gasto,
            'tipo_de_pago'=>$data->tipo_de_pago,
            'suplidor_id'=>$data->suplidor_id,
            'itebis'=>$data->itebis,
            'total'=>$data->total,
            'fecha_registro'=>date('ymdiis'),
            'descripcion'=>$data->descripcion,
            'comprobante_fiscal'=>$data->comprobante_fiscal
           ]);
  
               return "Gasto registrado con exito";
   
            }catch(Exception $e){
   
               return $e;
   
            }


    }


    public function cargar_gasto($id){

        $data = DB::table('gastos')->where('id',$id)->get();
        return $data;

    }

    public function eliminar_gasto(Request $data){

            try{

                DB::table('gastos')->where('id',$data->id)->delete();
                return "Registro eliminado con exito";

            }catch(Exception $e){

                return $e;
            }


    }

    public function cargar_gastos(){

    
        $data= DB::table("gastos")->select("gastos.*","suplidores.nombre")->join("suplidores","gastos.suplidor_id","=","suplidores.id")->orderBy("gastos.id","desc")->limit(20)->get();
       
        return $data;

    }

    public function buscar_gasto($id){


        $data= DB::table("gastos")->select("gastos.*","suplidores.nombre")->join("suplidores","gastos.suplidor_id","=","suplidores.id")->where("gastos.id",$id)->orderBy("gastos.id","desc")->limit(20)->get();

        return $data;

    }

    public function buscar_por_fecha($fecha_i,$fecha_f){


           // return $fecha_i." ".$fecha_f;
            if($fecha_i=="" && $fecha_f==""){

                return DB::table("gastos")->join("suplidores","gastos.suplidor_id","=","suplidores.id")->where("fecha_registro",date('ymd'))->get();
                
            }else{  

                //$fecha_i." 00:00:00";
                $fecha_f." 23:59:59";

                return DB::table("gastos")->select("gastos.*","suplidores.nombre")->join("suplidores","gastos.suplidor_id","=","suplidores.id")->whereBetween("gastos.fecha_registro",array($fecha_i,$fecha_f))->get();

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
            return DB::table("gastos")->where("fecha_registro",$fecha_i)->sum('total');
      
       }else{

            return DB::table("gastos")->where("fecha_registro",">=",$fecha_i)->where("fecha_registro","<=",$fecha_i)->sum('total');

       }    
    
    
     
    }




}
