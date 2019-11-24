<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App;
class ControllerFactura extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $data)
    {

       $total =  $data->input('total');
       $cantidad = $data->input('cantidad');
       $id_doctor = $data->input('id_doctor');
       $id_paciente = $data->input('id_paciente');
       $id_paciente = array("id_factura"=>$id_paciente);

        //return $id_doctor;
       $procedimientos = $data->input('procedimientos');
       $procedimientosx = $procedimientos[0];
       $cantidad = count($procedimientosx);
       $array_new = [];
       function array_push_assoc($array, $key, $value){

                $array[$key] = $value;
                return $array;

        }

        for($i=0;$i<$cantidad;$i++){
        

                $array_new[] = array_push_assoc($procedimientosx[$i],'id_factura','20');


        }
        

        return $array_new;
        DB::table('historial_ps')->insert($array_new);
    

    
    }

    public function ConsultarProcedimientos($id){

        $persona = App\Procedimiento::find($id);
    
        return $persona;
    }

    public function cargar_facturas(){

            $data = DB::table('facturas')->join('historial_ps','facturas.id','=','historial_ps.id_factura')->get();
            return $data;
    }

    public function cargar_procedimientos_factura($id_factura){

        $data = DB::table('historial_ps')->where('id_factura','=',$id_factura)->get();
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
