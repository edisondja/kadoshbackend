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

        return App\Paciente::withSum('estatus:precio_estatus')->with('doctor')->take(30)->orderBy("id","desc")->get();
        
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function guardar(Request $data){
        //metodo para crear un paciente

        if($data->hasFile("foto_paciente")){
            
            $archivo = $data->file('foto_paciente')->store("public");
            $archivo = explode("/",$archivo);
    
        }else{

            $archivo= array("0"=>"","1"=>"");
        }

 
        $paciente = new App\Paciente();
        $paciente->nombre = $data->nombre;
        $paciente->apellido =  $data->apellido;
        $paciente->telefono = $data->telefono;
        $paciente->id_doctor = $data->id_doctor;
        $paciente->cedula = $data->cedula;
        $paciente->correo_electronico = $data->correo_electronico;
        $paciente->fecha_de_ingreso=date("Y-m-d H:i:s");
        $paciente->fecha_nacimiento=$data->fecha_nacimiento;
        $paciente->foto_paciente= $archivo[1];
        $paciente->nombre_tutor = $data->nombre_tutor;
        $paciente->sexo = $data->sexo;
        $paciente->save();

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
        $paciente = App\Paciente::with("doctor")->find($id_paciente);
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
    public function update(Request $data)
    {                   

        $Paciente = App\Paciente::find($data->id);
        $Paciente->nombre = $data->nombre;
        $Paciente->apellido = $data->apellido;
        $Paciente->cedula = $data->cedula;
        $Paciente->telefono = $data->telefono;
        $Paciente->sexo = $data->sexo;
        $Paciente->correo_electronico = $data->correo_electronico;
        $Paciente->fecha_nacimiento = $data->fecha_nacimiento;
        $Paciente->id_doctor = $data->id_doctor;
        $Paciente->nombre_tutor = $data->nombre_tutor;
        $Paciente->save();
        return "Cliente acutalizado correctamente";
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id_paciente)
    {
        try {
            // Validar clave secreta
            $claveSecreta = $request->input('clave_secreta');
            $config = App\Config::first();
            
            if (!$config || !$config->clave_secreta) {
                return response()->json([
                    'error' => 'Error de configuración',
                    'message' => 'No se ha configurado una clave secreta. Por favor configúrela primero.'
                ], 400);
            }

            if ($claveSecreta !== $config->clave_secreta) {
                return response()->json([
                    'error' => 'Clave secreta incorrecta',
                    'message' => 'La clave secreta proporcionada no es correcta. No se puede eliminar el perfil.'
                ], 403);
            }

            // Registrar en auditoría
            $usuarioId = $request->input('usuario_id') ?? $request->header('usuario_id') ?? null;
            if ($usuarioId) {
                $paciente = App\Paciente::find($id_paciente);
                if ($paciente) {
                    \App\Helpers\AuditoriaHelper::registrar(
                        $usuarioId,
                        'Pacientes',
                        'Eliminar Paciente',
                        "Paciente #{$id_paciente} eliminado: {$paciente->nombre} {$paciente->apellido}"
                    );
                }
            }

            DB::table("facturas")->where('id_paciente','=',$id_paciente)->delete();
            $registro = App\Paciente::find($id_paciente);
            $registro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Paciente eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar paciente: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al eliminar paciente',
                'message' => $e->getMessage()
            ], 500);
        }
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
            
            $query = App\Paciente::query()->withSum('estatus:precio_estatus')->with('doctor');

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

         if($pacientes==0){

              return ['cantidad_pacientes'=>0];
         }else{

            return ['cantidad_pacientes'=>$pacientes];

         }
     

    }

    public function actualizar_foto_paciente(Request $data){
        

        $archivo = $data->file('foto_paciente')->store("public");
        $archivo = explode("/",$archivo);

        $paciente = App\Paciente::find($data->id);
        $paciente->foto_paciente = $archivo[1];
        $paciente->save();

        return $archivo[1];


    }

    /**
     * Exportar todos los pacientes a JSON
     */
    public function exportar_pacientes()
    {
        try {
            $pacientes = App\Paciente::all();
            
            // Convertir a array y remover timestamps si es necesario
            $data = $pacientes->map(function($paciente) {
                return [
                    'nombre' => $paciente->nombre,
                    'apellido' => $paciente->apellido,
                    'cedula' => $paciente->cedula,
                    'telefono' => $paciente->telefono,
                    'correo_electronico' => $paciente->correo_electronico,
                    'fecha_nacimiento' => $paciente->fecha_nacimiento,
                    'fecha_de_ingreso' => $paciente->fecha_de_ingreso,
                    'sexo' => $paciente->sexo,
                    'nombre_tutor' => $paciente->nombre_tutor,
                    'id_doctor' => $paciente->id_doctor,
                    'foto_paciente' => $paciente->foto_paciente
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $data->count(),
                'fecha_exportacion' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al exportar pacientes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importar pacientes desde JSON
     */
    public function importar_pacientes(Request $request)
    {
        try {
            $request->validate([
                'datos' => 'required|array',
                'datos.*.nombre' => 'required|string',
                'datos.*.apellido' => 'required|string',
                'datos.*.cedula' => 'nullable|string',
                'datos.*.telefono' => 'nullable|string',
                'datos.*.fecha_nacimiento' => 'nullable|date',
                'datos.*.sexo' => 'nullable|string',
                'datos.*.id_doctor' => 'nullable|integer|exists:doctors,id'
            ]);

            $datos = $request->datos;
            $importados = 0;
            $errores = [];

            foreach ($datos as $index => $dato) {
                try {
                    // Verificar si el paciente ya existe por cédula
                    $existe = null;
                    if (!empty($dato['cedula'])) {
                        $existe = App\Paciente::where('cedula', $dato['cedula'])->first();
                    }

                    if ($existe) {
                        // Actualizar paciente existente
                        $existe->nombre = $dato['nombre'];
                        $existe->apellido = $dato['apellido'];
                        $existe->telefono = $dato['telefono'] ?? $existe->telefono;
                        $existe->correo_electronico = $dato['correo_electronico'] ?? $existe->correo_electronico;
                        $existe->fecha_nacimiento = $dato['fecha_nacimiento'] ?? $existe->fecha_nacimiento;
                        $existe->sexo = $dato['sexo'] ?? $existe->sexo;
                        $existe->nombre_tutor = $dato['nombre_tutor'] ?? $existe->nombre_tutor;
                        $existe->id_doctor = $dato['id_doctor'] ?? $existe->id_doctor;
                        $existe->save();
                        $importados++;
                    } else {
                        // Crear nuevo paciente
                        $paciente = new App\Paciente();
                        $paciente->nombre = $dato['nombre'];
                        $paciente->apellido = $dato['apellido'];
                        $paciente->cedula = $dato['cedula'] ?? null;
                        $paciente->telefono = $dato['telefono'] ?? null;
                        $paciente->correo_electronico = $dato['correo_electronico'] ?? null;
                        $paciente->fecha_nacimiento = $dato['fecha_nacimiento'] ?? null;
                        $paciente->fecha_de_ingreso = $dato['fecha_de_ingreso'] ?? Carbon::now();
                        $paciente->sexo = $dato['sexo'] ?? null;
                        $paciente->nombre_tutor = $dato['nombre_tutor'] ?? null;
                        $paciente->id_doctor = $dato['id_doctor'] ?? 1; // Default doctor
                        $paciente->foto_paciente = $dato['foto_paciente'] ?? null;
                        $paciente->save();
                        $importados++;
                    }
                } catch (\Exception $e) {
                    $errores[] = [
                        'fila' => $index + 1,
                        'error' => $e->getMessage(),
                        'datos' => $dato
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Importación completada",
                'importados' => $importados,
                'total' => count($datos),
                'errores' => $errores
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error de validación',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al importar pacientes',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
