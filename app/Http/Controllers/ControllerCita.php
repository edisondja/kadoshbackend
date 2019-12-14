<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App;

class ControllerCita extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data = DB::table("citas")->take(20)->get();
        return $data;
    }

    public function citas_paciente($id){


                $citas = DB::table("citas")->where("id_paciente","=",$id)->orderBy("dia","desc")->get();
                return $citas;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id_paciente,$hora,$dia)
    {
        $cita =new App\Cita();
        $cita->id_paciente = $id_paciente;
        $cita->hora= $hora;
        $cita->dia= $dia;
        $cita->save();

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function BuscarCita($fecha)
    {   
        $citas = DB::table('citas')->where('dia','=',$fecha)->get();
        return $citas;
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = App\Cita::find($id);
        return $data;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return App\Cita::find($id);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id_cita,$hora,$dia)
    {
        $actualizar = App\Cita::find($id_cita);
        $actualizar->hora = $hora;
        $actualizar->dia = $dia;
        $actualizar->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $eliminar = App\Cita::find($id);
        $eliminar->delete();

    }
}
