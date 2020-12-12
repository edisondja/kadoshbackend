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

        return App\Paciente::with("estatus")->orderBy("id","desc")->take(30)->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function guardar($nombre,$apellido,$telefono,$id_doctor,$cedula,$fecha_nacimiento,$sexo)

    {
        //metodo para crear un paciente

        $paciente = new App\Paciente();
        $paciente->nombre = $nombre;
        $paciente->apellido =  $apellido;
        $paciente->telefono = $telefono;
        $paciente->id_doctor = $id_doctor;
        $paciente->cedula = $cedula;
        $paciente->fecha_de_ingreso=date("Y-m-d H:i:s");
        $paciente->fecha_nacimiento=$fecha_nacimiento;
        $paciente->sexo = $sexo;
        
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
    public function show($id_paciente)
    {
        //
        $paciente = App\Paciente::find($id_paciente);
        return $paciente;

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
    public function update($nombre,$apellido,$cedula,$telefono,$sexo,$fecha_nacimiento,$id_doctor,$id)
    {                   
        
        $Paciente = App\Paciente::find($id);

        $Paciente->nombre = $nombre;
        $Paciente->apellido = $apellido;
        $Paciente->cedula = $cedula;
        $Paciente->telefono = $telefono;
        $Paciente->sexo = $sexo;
        $Paciente->fecha_nacimiento = $fecha_nacimiento;
        $Paciente->id_doctor = $id_doctor;
        $Paciente->save();
        return "Cliente acutalizado correctamente";
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
        DB::table("facturas")->where('id_paciente','=',$id_paciente)->delete();
        $registro = App\Paciente::find($id_paciente);
        $registro->delete();


    }

    public function Notificar_cumple(){

        $paciente= App\Paciente::whereDay('fecha_nacimiento', '=',date('d'))->whereMonth('fecha_nacimiento', '=',date('m'))->get();
    
        return $paciente;
    }
    public function buscando_paciente($q){

        
        //buscando el paciente por el filtro like
        //$data=DB::table('pacientes')->where("nombre","like","%$nombre%")->OrWhere("apellido","like","%$nombre%")->take(20)->get();
        //$data = App\Paciente::whereRaw("MATCH (nombre,apellido) AGAINST ($nombre)")->take(20)->get();


            $searchTerms = explode(' ', $q);
            
            $query = App\Paciente::query()->with("estatus");

            foreach($searchTerms as $searchTerm){
                $query->where(function($q) use ($searchTerm){
                    $q->where('nombre', 'like', '%'.$searchTerm.'%')
                    ->orWhere('apellido', 'like', '%'.$searchTerm.'%')
                    ->orWhere('telefono', 'like', '%'.$searchTerm.'%')
                    ->orWhere('cedula', 'like', '%'.$searchTerm.'%');
                    // and so on
                });
            }

            $results = $query->get();
        


        return  $results;
        
      

    }

    public function deuda_paciente($id_paciente){

        $deuda=DB::table("facturas")->where('id_paciente','=',$id_paciente)->sum('precio_estatus');

        return  ['deuda_total'=>number_format($deuda, 2, '.', ',')];
    }


}
