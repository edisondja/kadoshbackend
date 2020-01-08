<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use DB;

class Paciente extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data = DB::table('pacientes')->orderBy("id","desc")->get();

        return $data;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function guardar($nombre,$apellido,$telefono,$id_doctor,$cedula,$fecha_nacimiento)

    {
        //metodo para crear un paciente

        $paciente = new App\Paciente();
        $paciente->nombre = $nombre;
        $paciente->apellido =  $apellido;
        $paciente->telefono = $telefono;
        $paciente->id_doctor = 1;
        $paciente->cedula = $cedula;
        $paciente->fecha_nacimiento = $fecha_nacimiento;
        
        if($paciente->save()){

                echo "guardado con exito";
        }
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
        $data = App\Paciente::find($id);

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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($nombre,$apellido,$id_doctor,$cedula,$fecha_nacimiento,$id)
    {
    
        $Paciente = App\Paciente::find($id);

        $Paciente->nombre = $nombre;
        $Paciente->apellido = $apellido;
        $Paciente->id_doctor = $id_doctor;
        $Paciente->save();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id_paciente)
    {
        //
        DB::table("facturas")->where('id_paciente','=',$id_paciente);
        $registro = App\Paciente::find($id_paciente);
        $registro->delete();


    }

    public function Notificar_cumple(){

        $paciente= App\Paciente::whereDay('fecha_nacimiento', '=',date('d'))->whereMonth('fecha_nacimiento', '=',date('m'))->get();
    
        return $paciente;
    }
    public function buscando_paciente($nombre){


        $data=DB::table('doctors')->where("nombre","like","%$nombre%")->take(20)->get();

        return  $data;

    }


}
