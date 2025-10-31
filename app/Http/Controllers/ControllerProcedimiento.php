<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use DB;

class ControllerProcedimiento extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
        public function index()
        {
            //
            $data = DB::table("procedimientos")->where('estado','activo')->orderBy('id','desc')->get();
            if($data->isEmpty()){

                return  new App\Procedimiento();

            }else{
                return $data;
            }
        }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $data)
    {
        $procedimiento = new App\Procedimiento();
        $procedimiento->nombre = $data->nombre;
        $procedimiento->precio = $data->precio;
        $procedimiento->estado = 'activo';
        $procedimiento->save();

    }

 
    public function show($id)
    {
        $procedimiento = App\Procedimiento::find($id);
        return $procedimiento;
    }

    public function update(Request $data)
    {
        $procedimiento = App\Procedimiento::find($data->io);
        $procedimiento->nombre = $data->nombre;
        $procedimiento->precio = $data->precio;
        $procedimiento->save();
            
    }

    public function buscarProcedimiento($buscar){
    
        $data = DB::table("procedimientos")->where("nombre","like","%$buscar%")->where("estado","activo")->get();
        return $data;
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $desactivar = App\Procedimiento::find($id);
        $desactivar->estado='inactivo';
        $desactivar->save();
    }

    public function eliminar_procedimiento_lista($id_procedimiento,$id_factura,$total){

            //DB::table('historial_ps')->where('id','=',$id_procedimiento)->delete();
            App\historial_p::find($id_procedimiento)->delete();
            $factura = App\Factura::find($id_factura);  
            $calculo = ($factura->precio_estatus - $total);
            $factura->precio_estatus = $calculo;
           
            $factura->save();
            return "success";
    }

    public function agregar_procedimiento_a_lista($id_factura,$id_procedimiento,$total,$cantidad){

            DB::table('historial_ps')->insert([
                'id_factura'=>$id_factura,
                'id_procedimiento'=>$id_procedimiento,
                'cantidad'=>$cantidad,
                'total'=>$total
            ]);

            $factura =  App\Factura::find($id_factura);
            $factura->precio_estatus = ($factura->precio_estatus + $total);
            $factura->save();
                    
    }
}
