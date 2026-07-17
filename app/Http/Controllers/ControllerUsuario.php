<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Firebase\JWT\JWT;
use Mail;
use Carbon\Carbon;
use DB;
use App;
class ControllerUsuario extends Controller
{
    private function modulosSistema()
    {
        return [
            'cargar_pacientes', 'paciente', 'invitar_paciente', 'doctor',
            'asignar_ganancias_recibos', 'procedimiento', 'agregar_usuario', 'especialidades',
            'notificaciones', 'agregar_cita', 'contabilidad', 'nomina', 'punto_venta',
            'salarios_doctores', 'historial_pagos', 'consulta_deudas', 'reportes', 'auditoria',
            'configuracion', 'exportar_importar', 'administrar_tenants', 'manual_usuario',
        ];
    }

    private function permisosPorRol($rol)
    {
        $rol = trim((string) $rol);
        $all = [
            'cargar_pacientes' => true, 'paciente' => true, 'invitar_paciente' => true, 'doctor' => true,
            'asignar_ganancias_recibos' => true, 'procedimiento' => true, 'agregar_usuario' => true,
            'especialidades' => true, 'notificaciones' => true, 'agregar_cita' => true,
            'contabilidad' => true, 'nomina' => true, 'punto_venta' => true, 'salarios_doctores' => true,
            'historial_pagos' => true, 'consulta_deudas' => true, 'reportes' => true, 'auditoria' => true,
            'configuracion' => true, 'exportar_importar' => true, 'administrar_tenants' => true,
            'manual_usuario' => true,
        ];

        if ($rol === 'Administrador') return $all;
        if ($rol === 'Contable') {
            foreach (['paciente','invitar_paciente','doctor','asignar_ganancias_recibos','procedimiento','agregar_usuario','especialidades','notificaciones','agregar_cita','reportes','auditoria','configuracion','exportar_importar','administrar_tenants'] as $k) $all[$k] = false;
            return $all;
        }
        if ($rol === 'Secretaria') {
            foreach (['doctor','asignar_ganancias_recibos','procedimiento','agregar_usuario','especialidades','contabilidad','nomina','punto_venta','salarios_doctores','historial_pagos','consulta_deudas','reportes','auditoria','configuracion','exportar_importar','administrar_tenants'] as $k) $all[$k] = false;
            return $all;
        }
        if ($rol === 'Odontologo') {
            foreach (['doctor','asignar_ganancias_recibos','procedimiento','agregar_usuario','especialidades','contabilidad','nomina','punto_venta','salarios_doctores','historial_pagos','consulta_deudas','reportes','auditoria','configuracion','exportar_importar','administrar_tenants'] as $k) $all[$k] = false;
            return $all;
        }
        return $all;
    }

    private function permisosDesdeRolId($idRol)
    {
        if (empty($idRol)) return null;
        $base = $this->permisosPorRol('Administrador');
        foreach ($base as $k => $v) $base[$k] = false;
        $rows = DB::table('role_modulos')->where('id_rol', (int) $idRol)->where('permitido', 1)->get();
        foreach ($rows as $r) {
            if (array_key_exists($r->modulo, $base)) $base[$r->modulo] = true;
        }
        return $base;
    }

    private function normalizarPermisos($rol, $permisos, $idRol = null)
    {
        $desdeRol = $this->permisosDesdeRolId($idRol);
        if (is_array($desdeRol)) return $desdeRol;

        if ($rol !== 'Personalizado') {
            return $this->permisosPorRol($rol);
        }
        if (is_string($permisos)) {
            $decoded = json_decode($permisos, true);
            $permisos = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($permisos)) $permisos = [];
        $base = $this->permisosPorRol('Administrador');
        foreach ($base as $k => $v) $base[$k] = !empty($permisos[$k]);
        return $base;
    }

    private function usuarioConPermisos($usuario)
    {
        $arr = $usuario->toArray();
        $arr['permisos'] = $this->normalizarPermisos($usuario->roll, $usuario->permisos, $usuario->id_rol ?? null);
        if (!empty($usuario->id_rol)) {
            $rol = DB::table('roles')->where('id', (int) $usuario->id_rol)->first();
            if ($rol) $arr['rol_nombre'] = $rol->nombre;
        }
        return $arr;
    }

    public function usuarioTienePermiso($usuario, $modulo)
    {
        if (!$usuario) {
            return false;
        }
        $permisos = $this->normalizarPermisos($usuario->roll, $usuario->permisos, $usuario->id_rol ?? null);
        return !empty($permisos[$modulo]);
    }

    private function guardarFotoUsuario(Request $data, $fotoActual = null)
    {
        if (!$data->hasFile('foto_usuario')) {
            return $fotoActual;
        }
        $storedPath = $data->file('foto_usuario')->store('public');
        return basename(str_replace('\\', '/', $storedPath));
    }

    private function permisosDesdeRequest(Request $data)
    {
        $permisos = $data->input('permisos');
        if (is_string($permisos)) {
            $decoded = json_decode($permisos, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $permisos;
    }

    /** Calcula y asigna el JSON de permisos en el modelo (antes y después de crear). */
    private function guardarPermisosEnUsuario(App\Usuario $usuario, Request $data = null, $permisosRaw = null)
    {
        $permisosReq = $permisosRaw;
        if ($data) {
            $permisosReq = $this->permisosDesdeRequest($data);
        }
        $normalizados = $this->normalizarPermisos($usuario->roll, $permisosReq, $usuario->id_rol);
        $usuario->permisos = json_encode($normalizados, JSON_UNESCAPED_UNICODE);
        return $normalizados;
    }


    private function resolverIpCliente(Request $request)
    {
        $xff = $request->header('X-Forwarded-For');
        if ($xff) {
            $parts = explode(',', $xff);
            $ip = trim($parts[0]);
            if ($ip !== '') {
                return $ip;
            }
        }
        return $request->ip();
    }

    private function ubicacionAproximadaPorIp($ip)
    {
        if (!$ip) {
            return 'Ubicacion no disponible';
        }

        // IPs locales no son geolocalizables
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
            return 'Red local';
        }

        try {
            $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,regionName,city,query';
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true,
                ],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if (!$json) {
                return 'Ubicacion no disponible';
            }
            $data = @json_decode($json, true);
            if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
                return 'Ubicacion no disponible';
            }
            $pais = trim((string) ($data['country'] ?? ''));
            $region = trim((string) ($data['regionName'] ?? ''));
            $ciudad = trim((string) ($data['city'] ?? ''));

            $partes = array_values(array_filter([$pais, $region, $ciudad]));
            return count($partes) > 0 ? implode(' / ', $partes) : 'Ubicacion no disponible';
        } catch (\Exception $e) {
            return 'Ubicacion no disponible';
        }
    }

    private function enviarAvisoInicioSesion($usuario, Request $request)
    {
        try {
            $config = App\Config::first();
            $correoDestino = null;
            if ($config) {
                $correoDestino = trim((string) ($config->email_clinica ?: $config->email));
            }

            if (!$correoDestino || !filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {
                return; // no bloquear login si no hay correo configurado
            }

            $nombreClinica = 'OdontoED';
            if ($config) {
                $nombreClinica = trim((string) ($config->nombre_clinica ?: $config->nombre ?: 'OdontoED'));
            }

            $ip = $this->resolverIpCliente($request);
            $ubicacion = $this->ubicacionAproximadaPorIp($ip);
            $fechaHora = Carbon::now()->format('Y-m-d H:i:s');
            $navegador = (string) $request->header('User-Agent', 'No disponible');

            $nombreUsuario = trim(($usuario->nombre ?? '') . ' ' . ($usuario->apellido ?? ''));
            $asunto = 'Alerta de inicio de sesion - ' . $nombreClinica;

            Mail::send('emails.alerta_inicio_sesion', [
                'nombreClinica' => $nombreClinica,
                'nombreUsuario' => $nombreUsuario,
                'rol' => (string) ($usuario->roll ?? 'No disponible'),
                'fechaHora' => $fechaHora,
                'ip' => (string) $ip,
                'ubicacion' => $ubicacion,
                'navegador' => $navegador,
            ], function ($m) use ($correoDestino, $asunto) {
                $m->to($correoDestino)->subject($asunto);
            });
        } catch (\Exception $e) {
            \Log::warning('No se pudo enviar alerta de inicio de sesion: ' . $e->getMessage());
        }
    }

    public function login(Request $request, $usuario,$clave)
    {

        $usuario = App\Usuario::where("usuario",$usuario)->where("clave",$clave)->first();
        if (!$usuario) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

           

        $payload = array(
            "id"=>$usuario->id,
            "usuario"=>$usuario->nombre,
            "apellido"=>$usuario->apellido,
            "iat" => time(),
            'exp'=>time() + (1*60*60),
            "nbf" => 1357000000
        );
        
  
        $jwt = JWT::encode($payload,env("FIRMA_TOKEN"),'HS256');

        $this->enviarAvisoInicioSesion($usuario, $request);

        return [
            "id"=>$usuario->id,
            "nombre"=>$usuario->nombre,
            "apellido"=>$usuario->apellido,
            "token"=>$jwt,
            "roll"=>$usuario->roll,
            "id_rol"=>$usuario->id_rol,
            "permisos"=>$this->normalizarPermisos($usuario->roll, $usuario->permisos, $usuario->id_rol ?? null),
        ];
    
    }


    public function cargar_usuario($id_usuario){

            $usuario = App\Usuario::find($id_usuario);
            if (!$usuario) {
                return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }
            return $this->usuarioConPermisos($usuario);


    }


    public function agregar_usuario(Request $data){
        $data->validate([
            'usuario' => 'required|string|max:191',
            'clave' => 'required|string|min:4|max:191',
            'nombre' => 'required|string|max:191',
            'apellido' => 'required|string|max:191',
            'roll' => 'required|string|in:Administrador,Contable,Secretaria,Odontologo,Personalizado',
            'id_rol' => 'nullable|integer|min:1',
            'permisos' => 'nullable',
            'foto_usuario' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if (App\Usuario::where('usuario', trim((string) $data->usuario))->exists()) {
            return response()->json(['success' => false, 'message' => 'El usuario ya existe'], 422);
        }
        $usuario = new App\Usuario();
        $usuario->usuario = trim((string) $data->usuario);
        $usuario->clave =  $data->clave;
        $usuario->nombre = trim((string) $data->nombre);
        $usuario->apellido = trim((string) $data->apellido);
        $usuario->roll = trim((string) $data->roll);
        $usuario->id_rol = $data->input('id_rol') ?: null;
        if ($usuario->id_rol) {
            $rolBd = DB::table('roles')->where('id', (int) $usuario->id_rol)->first();
            if (!$rolBd) return response()->json(['success' => false, 'message' => 'Rol no válido'], 422);
            $usuario->roll = 'Personalizado';
        }
        $usuario->foto_usuario = $this->guardarFotoUsuario($data);
        $this->guardarPermisosEnUsuario($usuario, $data);
        if ($usuario->save()) {
            // Tras crear: persistir permisos en BD (usuarios antiguos quedaban con NULL)
            $this->guardarPermisosEnUsuario($usuario, $data);
            $usuario->save();
            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado con exito',
                'data' => $this->usuarioConPermisos($usuario),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No se pudo registrar el usuario'], 500);
    }


    public function actualizar_usuario(Request $data){

        $data->validate([
            'id_usuario' => 'required|integer|min:1',
            'usuario' => 'required|string|max:191',
            'clave' => 'required|string|min:4|max:191',
            'nombre' => 'required|string|max:191',
            'apellido' => 'required|string|max:191',
            'roll' => 'required|string|in:Administrador,Contable,Secretaria,Odontologo,Personalizado',
            'id_rol' => 'nullable|integer|min:1',
            'permisos' => 'nullable',
            'foto_usuario' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $usuario = App\Usuario::find($data->id_usuario);
        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }
        $otro = App\Usuario::where('usuario', trim((string) $data->usuario))->where('id', '!=', $usuario->id)->first();
        if ($otro) {
            return response()->json(['success' => false, 'message' => 'El usuario ya existe'], 422);
        }
        $usuario->usuario = trim((string) $data->usuario);
        $usuario->clave =  $data->clave;
        $usuario->nombre = trim((string) $data->nombre);
        $usuario->apellido = trim((string) $data->apellido);
        $usuario->roll = trim((string) $data->roll);
        $usuario->id_rol = $data->input('id_rol') ?: null;
        if ($usuario->id_rol) {
            $rolBd = DB::table('roles')->where('id', (int) $usuario->id_rol)->first();
            if (!$rolBd) return response()->json(['success' => false, 'message' => 'Rol no válido'], 422);
            $usuario->roll = 'Personalizado';
        }
        $usuario->foto_usuario = $this->guardarFotoUsuario($data, $usuario->foto_usuario);
        $this->guardarPermisosEnUsuario($usuario, $data);
        if ($usuario->save()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado con exito',
                'data' => $this->usuarioConPermisos($usuario),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No se pudo actualizar el usuario'], 500);
    }


    public function buscar_usuario($usuario){


        $usuario = App\Usuario::where('usuario','like',"%$usuario%")->get();
        return $usuario;
    


    }

        
    public function eliminar_usuario(Request $data){

        $usuario = App\Usuario::find($data->id_usuario);
        
        if($usuario->delete()){

           return "Usuario eliminado con exito"; 
        }
            

     
    }

    public function cantidad_de_usuarios(){


        return ['cantidad_de_usuarios'=>App\Usuario::count()];

    }

    public function cargar_usuarios(){
        $rows = App\Usuario::orderBy('id', 'desc')->take(100)->get();
        return $rows->map(function ($u) {
            return $this->usuarioConPermisos($u);
        })->values();



    }

    public function listar_roles()
    {
        $roles = DB::table('roles')->where('activo', 1)->orderBy('nombre')->get();
        $all = $this->permisosPorRol('Administrador');
        return $roles->map(function ($r) use ($all) {
            $perm = $all;
            foreach ($perm as $k => $v) $perm[$k] = false;
            $rows = DB::table('role_modulos')->where('id_rol', $r->id)->where('permitido', 1)->get();
            foreach ($rows as $rw) {
                if (array_key_exists($rw->modulo, $perm)) $perm[$rw->modulo] = true;
            }
            return [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'descripcion' => $r->descripcion,
                'permisos' => $perm,
            ];
        })->values();
    }

    public function guardar_rol(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'permisos' => 'required|array',
        ]);
        $idRol = (int) $request->input('id_rol', 0);
        $nombre = trim((string) $request->nombre);
        $descripcion = trim((string) $request->input('descripcion', ''));
        $permisos = $request->input('permisos', []);
        $allMods = $this->modulosSistema();

        DB::beginTransaction();
        try {
            if ($idRol > 0) {
                $rol = DB::table('roles')->where('id', $idRol)->first();
                if (!$rol) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Rol no encontrado'], 404);
                }
                DB::table('roles')->where('id', $idRol)->update([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'updated_at' => Carbon::now(),
                ]);
            } else {
                $dup = DB::table('roles')->where('nombre', $nombre)->first();
                if ($dup) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Ese nombre de rol ya existe'], 422);
                }
                $idRol = DB::table('roles')->insertGetId([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'activo' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            DB::table('role_modulos')->where('id_rol', $idRol)->delete();
            foreach ($allMods as $m) {
                if (!empty($permisos[$m])) {
                    DB::table('role_modulos')->insert([
                        'id_rol' => $idRol,
                        'modulo' => $m,
                        'permitido' => 1,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Rol guardado con éxito', 'id_rol' => $idRol]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'No se pudo guardar el rol'], 500);
        }
    }

    public function eliminar_rol(Request $request)
    {
        $request->validate(['id_rol' => 'required|integer|min:1']);
        $idRol = (int) $request->id_rol;
        $enUso = App\Usuario::where('id_rol', $idRol)->count();
        if ($enUso > 0) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar: hay usuarios asignados a este rol'], 422);
        }
        DB::table('role_modulos')->where('id_rol', $idRol)->delete();
        DB::table('roles')->where('id', $idRol)->delete();
        return response()->json(['success' => true, 'message' => 'Rol eliminado']);
    }

    /**
     * Exportar todos los usuarios a JSON
     */
    public function exportar_usuarios()
    {
        try {
            $usuarios = App\Usuario::all();
            
            // Convertir a array (sin incluir la clave por seguridad)
            $data = $usuarios->map(function($usuario) {
                return [
                    'usuario' => $usuario->usuario,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'roll' => $usuario->roll
                    // No exportamos la clave por seguridad
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $data->count(),
                'fecha_exportacion' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al exportar usuarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importar usuarios desde JSON
     */
    public function importar_usuarios(Request $request)
    {
        try {
            $request->validate([
                'datos' => 'required|array',
                'datos.*.usuario' => 'required|string',
                'datos.*.nombre' => 'required|string',
                'datos.*.apellido' => 'required|string',
                'datos.*.roll' => 'required|string|in:Administrador,Doctor,Secretaria,Odontologo,Contable,Personalizado',
                'datos.*.clave' => 'nullable|string' // Clave opcional para importación
            ]);

            $datos = $request->datos;
            $importados = 0;
            $errores = [];

            foreach ($datos as $index => $dato) {
                try {
                    // Verificar si el usuario ya existe
                    $existe = App\Usuario::where('usuario', $dato['usuario'])->first();

                    if ($existe) {
                        // Actualizar usuario existente
                        $existe->nombre = $dato['nombre'];
                        $existe->apellido = $dato['apellido'];
                        $existe->roll = $dato['roll'];
                        $this->guardarPermisosEnUsuario($existe, null, $dato['permisos'] ?? null);
                        if (!empty($dato['clave'])) {
                            $existe->clave = $dato['clave'];
                        }
                        $existe->save();
                        $this->guardarPermisosEnUsuario($existe, null, $dato['permisos'] ?? null);
                        $existe->save();
                        $importados++;
                    } else {
                        // Crear nuevo usuario
                        $usuario = new App\Usuario();
                        $usuario->usuario = $dato['usuario'];
                        $usuario->nombre = $dato['nombre'];
                        $usuario->apellido = $dato['apellido'];
                        $usuario->roll = $dato['roll'];
                        $usuario->clave = $dato['clave'] ?? '123456'; // Clave por defecto si no se proporciona
                        $this->guardarPermisosEnUsuario($usuario, null, $dato['permisos'] ?? null);
                        $usuario->save();
                        $this->guardarPermisosEnUsuario($usuario, null, $dato['permisos'] ?? null);
                        $usuario->save();
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
                'error' => 'Error al importar usuarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

   
}
