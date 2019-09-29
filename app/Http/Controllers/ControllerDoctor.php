<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App;

class ControllerDoctor extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $doctors = DB::table('doctors')->orderBy('id','desc')->get();

        return $doctors;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($nombre,$apellido,$cedula,$telefono)
    {
        
        $doctor = new App\Doctor();
        $doctor->nombre= $nombre;
        $doctor->apellido= $apellido;
        $doctor->dni= $cedula;
        $doctor->numero_telefono=$telefono;
        $doctor->save();

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
    public function buscando_doctor($nombre)
    {
    
        $data = DB::table("doctors")->where("nombre","like","%$nombre%")->take(20)->get();

        return $data;

    }

    public function cargar_doctor($id){
    
        
        $data =DB::table("doctors")->where("id",$id)->get();
        return $data;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id 
     * @return \Illuminate\Http\Response
     */
    public function edit($nombre,$apellido,$cedula,$telefono,$id)
    {
        //
        $doctor = App\Doctor::find($id);
        $doctor->nombre= $nombre;
        $doctor->apellido= $apellido;
        $doctor->dni= $cedula;
        $doctor->numero_telefono= $telefono;
        $doctor->save();

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
    
           $doctor = App\Doctor::find($id);
           $doctor->delete();

    }
}
