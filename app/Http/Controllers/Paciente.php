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

        $perPage = (int) $request->query('per_page', 8);
        $perPage = max(1, min($perPage, 100));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = App\Paciente::withSum('estatus as estatus_precio_estatus_sum', 'precio_estatus')
            ->with('doctor')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function guardar(Request $data){
        try {
            $data->validate([
                'nombre' => 'required|string|max:191',
                'apellido' => 'required|string|max:191',
                'telefono' => 'required|string|max:50',
                'id_doctor' => 'required|integer|min:1',
                'sexo' => 'nullable|in:h,m',
                'correo_electronico' => 'nullable|email|max:191',
                'cedula' => 'nullable|string|max:50',
                'fecha_nacimiento' => 'nullable|date',
                'nombre_tutor' => 'nullable|string|max:191',
                'foto_paciente' => 'nullable|image|max:5120',
            ], [
                'nombre.required' => 'El nombre del paciente es obligatorio.',
                'apellido.required' => 'El apellido del paciente es obligatorio.',
                'telefono.required' => 'El teléfono del paciente es obligatorio.',
                'id_doctor.required' => 'Debe seleccionar el doctor que ingresa al paciente.',
                'id_doctor.integer' => 'El doctor seleccionado no es válido.',
                'correo_electronico.email' => 'El correo electrónico no es válido.',
                'foto_paciente.image' => 'La foto debe ser una imagen válida.',
                'foto_paciente.max' => 'La foto no puede superar 5 MB.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        if (!App\Doctor::where('id', (int) $data->id_doctor)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El doctor seleccionado no existe.',
            ], 422);
        }

        try {
            $nombreFoto = '';
            if ($data->hasFile('foto_paciente')) {
                $storedPath = $data->file('foto_paciente')->store('public');
                $nombreFoto = basename(str_replace('\\', '/', $storedPath));
            }

            $sexo = trim((string) ($data->sexo ?? ''));
            if (!in_array($sexo, ['h', 'm'], true)) {
                $sexo = 'h';
            }

            $paciente = new App\Paciente();
            $paciente->nombre = trim((string) $data->nombre);
            $paciente->apellido = trim((string) $data->apellido);
            $paciente->telefono = trim((string) $data->telefono);
            $paciente->id_doctor = (int) $data->id_doctor;
            $paciente->cedula = trim((string) ($data->cedula ?? '')) ?: '';
            $paciente->correo_electronico = trim((string) ($data->correo_electronico ?? '')) ?: null;
            $paciente->fecha_de_ingreso = date('Y-m-d H:i:s');
            $paciente->fecha_nacimiento = !empty(trim((string) ($data->fecha_nacimiento ?? '')))
                ? $data->fecha_nacimiento
                : '1900-01-01';
            $paciente->foto_paciente = $nombreFoto;
            $paciente->nombre_tutor = trim((string) ($data->nombre_tutor ?? '')) ?: null;
            $paciente->sexo = $sexo;
            $paciente->save();

            return response()->json([
                'success' => true,
                'message' => 'Paciente registrado correctamente.',
                'paciente' => $paciente,
            ] + $paciente->toArray());
        } catch (\Exception $e) {
            \Log::error('Error al guardar paciente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar el paciente. Verifique los datos e intente de nuevo.',
            ], 500);
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
        $Paciente->cedula = trim((string) ($data->cedula ?? '')) ?: '';
        $Paciente->telefono = $data->telefono;
        $Paciente->sexo = $data->sexo;
        $Paciente->correo_electronico = trim((string) ($data->correo_electronico ?? '')) ?: null;
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
        try {
            $deuda = DB::table('facturas')
                ->where('id_paciente', '=', $id_paciente)
                ->sum('precio_estatus');

            $deuda_total = $deuda !== null ? (float) $deuda : 0;

            return response()->json([
                'deuda_total' => $deuda_total
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al consultar la deuda',
                'message' => $e->getMessage(),
                'deuda_total' => 0
            ], 500);
        }
    }

    /**
     * Listado de deuda por paciente: mismas facturas que importan para deuda_paciente, pero solo las que tienen saldo.
     * Incluye todas las facturas con precio_estatus > 0 y suma ese campo por paciente (igual criterio que consultar deuda).
     * Opcional: fecha_desde + fecha_hasta acotan por fecha de creación de la factura.
     */
    public function listarDeudasPorFechas(Request $request)
    {
        $desde = $request->query('fecha_desde');
        $hasta = $request->query('fecha_hasta');
        $tieneDesde = $desde !== null && trim((string) $desde) !== '';
        $tieneHasta = $hasta !== null && trim((string) $hasta) !== '';
        $filtrarFecha = $tieneDesde || $tieneHasta;

        $iniCarbon = null;
        $finCarbon = null;

        if ($filtrarFecha) {
            if (!$tieneDesde || !$tieneHasta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Para filtrar por fecha envíe fecha_desde y fecha_hasta, o ninguna para ver toda la deuda.',
                ], 422);
            }

            $request->merge(['fecha_desde' => $desde, 'fecha_hasta' => $hasta]);
            $request->validate([
                'fecha_desde' => 'date',
                'fecha_hasta' => 'date|after_or_equal:fecha_desde',
            ], [
                'fecha_hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            ]);

            try {
                $iniCarbon = Carbon::parse($desde)->startOfDay();
                $finCarbon = Carbon::parse($hasta)->endOfDay();
                if ($iniCarbon->diffInDays($finCarbon) > 731) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El rango máximo permitido es 24 meses.',
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fechas no válidas.',
                ], 422);
            }
        }

        try {
            $q = DB::table('facturas')
                ->join('pacientes', 'facturas.id_paciente', '=', 'pacientes.id')
                ->where('facturas.precio_estatus', '>', 0);

            if ($filtrarFecha) {
                $q->where('facturas.created_at', '>=', $iniCarbon)
                    ->where('facturas.created_at', '<=', $finCarbon);
            }

            $rows = $q
                ->select(
                    'pacientes.id as id_paciente',
                    'pacientes.id_doctor',
                    'pacientes.nombre',
                    'pacientes.apellido',
                    'pacientes.cedula',
                    'pacientes.telefono',
                    DB::raw('SUM(facturas.precio_estatus) as deuda'),
                    DB::raw('COUNT(facturas.id) as cantidad_facturas')
                )
                ->groupBy(
                    'pacientes.id',
                    'pacientes.id_doctor',
                    'pacientes.nombre',
                    'pacientes.apellido',
                    'pacientes.cedula',
                    'pacientes.telefono'
                )
                ->orderByDesc('deuda')
                ->get();

            $total = 0.0;
            $pacientes = $rows->map(function ($r) use (&$total) {
                $d = (float) $r->deuda;
                $total += $d;

                return [
                    'id_paciente' => (int) $r->id_paciente,
                    'id_doctor' => $r->id_doctor !== null ? (int) $r->id_doctor : null,
                    'nombre' => $r->nombre,
                    'apellido' => $r->apellido,
                    'cedula' => $r->cedula,
                    'telefono' => $r->telefono,
                    'deuda' => $d,
                    'cantidad_facturas' => (int) $r->cantidad_facturas,
                ];
            });

            return response()->json([
                'success' => true,
                'filtrar_por_fecha_creacion' => $filtrarFecha,
                'fecha_desde' => $filtrarFecha ? $desde : null,
                'fecha_hasta' => $filtrarFecha ? $hasta : null,
                'total_deuda' => round($total, 2),
                'total_pacientes' => $pacientes->count(),
                'pacientes' => $pacientes,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar deudas.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
        

        $storedPath = $data->file('foto_paciente')->store('public');
        $nombreArchivo = basename(str_replace('\\', '/', $storedPath));

        $paciente = App\Paciente::find($data->id);
        $paciente->foto_paciente = $nombreArchivo;
        $paciente->save();

        return $nombreArchivo;


    }

    /**
     * Sirve archivos de storage/app/public como /storage/{archivo}
     * (respaldo si no existe el enlace simbólico public/storage → storage/app/public).
     */
    public function servirArchivoPublico($archivo)
    {
        $archivo = basename(str_replace('\\', '/', (string) $archivo));
        if ($archivo === '' || $archivo === '.' || $archivo === '..') {
            abort(404);
        }

        $full = storage_path('app/public/'.$archivo);
        $root = realpath(storage_path('app/public'));
        $real = realpath($full);

        if ($root === false || $real === false || strpos($real, $root) !== 0 || !is_file($real)) {
            abort(404);
        }

        return response()->file($real);
    }

    private function denegarSiNoPuedeExportar(Request $request)
    {
        $usuarioId = $request->header('usuario_id') ?? $request->input('usuario_id');
        if (!$usuarioId) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no válida. Inicie sesión nuevamente.'
            ], 401);
        }

        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no válida. Inicie sesión nuevamente.'
            ], 401);
        }

        try {
            $decoded = JWT::decode($token, env('FIRMA_TOKEN'), ['HS256']);
            if ((int) ($decoded->id ?? 0) !== (int) $usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no válida para este usuario.'
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión inválida o expirada.'
            ], 401);
        }

        $usuario = App\Usuario::find($usuarioId);
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 403);
        }

        $controllerUsuario = app(ControllerUsuario::class);
        if (!$controllerUsuario->usuarioTienePermiso($usuario, 'exportar_importar')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para exportar pacientes.'
            ], 403);
        }

        return null;
    }

    /**
     * Exportar todos los pacientes a JSON
     */
    public function exportar_pacientes(Request $request)
    {
        try {
            $denegado = $this->denegarSiNoPuedeExportar($request);
            if ($denegado) {
                return $denegado;
            }

            $pacientes = App\Paciente::with('doctor')->orderBy('id', 'desc')->get();

            $data = $pacientes->map(function ($paciente) {
                $doctor = $paciente->doctor;
                $nombreDoctor = $doctor
                    ? trim(($doctor->nombre ?? '') . ' ' . ($doctor->apellido ?? ''))
                    : '';

                $sexo = trim((string) ($paciente->sexo ?? ''));
                if ($sexo === 'h') {
                    $sexoLabel = 'Masculino';
                } elseif ($sexo === 'm') {
                    $sexoLabel = 'Femenino';
                } else {
                    $sexoLabel = $sexo;
                }

                return [
                    'id' => $paciente->id,
                    'nombre' => $paciente->nombre,
                    'apellido' => $paciente->apellido,
                    'cedula' => $paciente->cedula,
                    'telefono' => $paciente->telefono,
                    'correo_electronico' => $paciente->correo_electronico,
                    'fecha_nacimiento' => $paciente->fecha_nacimiento,
                    'fecha_de_ingreso' => $paciente->fecha_de_ingreso,
                    'sexo' => $sexo,
                    'sexo_label' => $sexoLabel,
                    'nombre_tutor' => $paciente->nombre_tutor,
                    'id_doctor' => $paciente->id_doctor,
                    'doctor' => $nombreDoctor,
                    'foto_paciente' => $paciente->foto_paciente,
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
