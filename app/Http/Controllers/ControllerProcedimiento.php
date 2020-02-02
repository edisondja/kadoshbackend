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
        $data = DB::table("procedimientos")->orderBy('id','desc')->get();
        return $data;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($nombre,$precio)
    {
        $procedimiento = new App\Procedimiento();
        $procedimiento->nombre = $nombre;
        $procedimiento->precio = $precio;
        $procedimiento->save();

    }

 
    public function show($id)
    {
        $procedimiento = App\Procedimiento::find($id);
        return $procedimiento;
    }

    public function update($nombre,$precio,$id)
    {
        $procedimiento = App\Procedimiento::find($id);
        $procedimiento->nombre = $nombre;
        $procedimiento->precio = $precio;
        $procedimiento->save();
            
    }

    public function buscarProcedimiento($buscar){
    
        $data = DB::table("procedimientos")->where("nombre","like","%$buscar%")->get();
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
        $eliminar = App\Procedimiento::find($id);
        $eliminar->delete();
    }

    public function eliminar_procedimiento_lista($id_procedimiento,$id_factura,$total){

            //DB::table('historial_ps')->where('id','=',$id_procedimiento)->delete();
            App\historial_p::find($id_procedimiento)->delete();
            $factura = App\Factura::find($id_factura);
            $factura->precio_estatus = ($factura->precio_estatus  - $total);
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
