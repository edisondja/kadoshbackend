<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Firebase\JWT\JWT;
use App;
use DB;
use Carbon\Carbon;

//Paciente control
class Paciente extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function index(Request $request)
    {

        //return ['token_llego'=>$request->bearerToken()];
        //Este es el token e cuidadito
       // $DECO = JWT::decode($request->bearerToken(),env("FIRMA_TOKEN"),array('HS256'));
       
        //print_r($DECO);
        //gidie();
        //
        // return $request->getContent();
        
      //  return ['token_llego'=>$request->bearerToken()];

       // dd("Este es el token!!!".$request->bearerToken());

        return App\Paciente::withSum('estatus:precio_estatus')->take(30)->orderBy("id","desc")->get();
        
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function guardar(Request $data){
        //metodo para crear un paciente

        $paciente = new App\Paciente();
        $paciente->nombre = $data->$nombre;
        $paciente->apellido =  $data->$apellido;
        $paciente->telefono = $data->$telefono;
        $paciente->id_doctor = $data->$id_doctor;
        $paciente->cedula = $data->$cedula;
        $paciente->fecha_de_ingreso=date("Y-m-d H:i:s");
        $paciente->fecha_nacimiento=$data->$fecha_nacimiento;
        $paciente->foto_paciente="";
        $paciente->sexo = $data->$sexo;


        /*
        formData.append("foto_paciente", imagefile.files[0]);
        formData.append("nombre",document.getElementById("nombre").value);
        formData.append("apellido",document.getElementById("apellido").value);
        formData.append("cedula",document.getElementById("cedula").value);
        formData.append("telefono",document.getElementById("telefono").value);
        formData.append("id_doctor",document.getElementById("doctores_select").value);
        formData.append("fecha_nacimiento",document.getElementById("fecha_nacimiento").value);
        formData.append("sexo",document.getElementById("sexo").value);
        */


        
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

    /**Cosilla nuevas
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
            
            $query = App\Paciente::query()->withSum('estatus:precio_estatus');

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

    public function cargar_generos(){

            //select sexo,COUNT(*) from pacientes  GROUP BY sexo;
           
            $results = DB::select( DB::raw("select sexo,COUNT(*) as cantidad from pacientes  GROUP BY sexo") );
            //return $results[2]->cantidad;
            return [
                ['hombres'=>$results[2]->cantidad],
                ['mujeres'=>$results[3]->cantidad]
            ];

    }

    public function cantidad_de_pacientes(){

         $pacientes = App\Paciente::count();    

         return ['cantidad_pacientes'=>$pacientes];

    }


}
