<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Firebase\JWT\JWT;
use DB;
use App;
class ControllerUsuario extends Controller
{

    public function login($usuario,$clave)
    {

        $usuario = App\Usuario::where("usuario",$usuario)->where("clave",$clave)->first();

           

        $payload = array(
            "id"=>$usuario->id,
            "usuario"=>$usuario->nombre,
            "apellido"=>$usuario->apellido,
            "iat" => time(),
            'exp'=>time() + (1*60*60),
            "nbf" => 1357000000
        );
        
  
        $jwt = JWT::encode($payload,env("FIRMA_TOKEN"),'HS256');


        return ["id"=>$usuario->id,"nombre"=>$usuario->nombre,"apellido"=>$usuario->apellido,"token"=>$jwt,"roll"=>$usuario->roll];
    
    }


    public function cargar_usuario($id_usuario){

            $usuario = App\Usuario::find($id_usuario);
            return $usuario;


    }


    public function agregar_usuario(Request $data){


        $usuario = new App\Usuario();
        $usuario->usuario = $data->usuario;
        $usuario->clave =  $data->clave;
        $usuario->nombre = $data->nombre;
        $usuario->apellido = $data->apellido;
        $usuario->roll = $data->roll;
        if($usuario->save()){

            return "Usuario registrado con exito";
        }


    }


    public function actualizar_usuario(Request $data){

    

        $usuario = App\Usuario::find($data->id_usuario);
        $usuario->usuario = $data->usuario;
        $usuario->clave =  $data->clave;
        $usuario->nombre = $data->nombre;
        $usuario->apellido = $data->apellido;
        $usuario->roll = $data->roll;
        if($usuario->save()){

            return "Usuario actualizado con exito";
        }

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


        
        return  App\Usuario::get()->take(20);



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
                'datos.*.roll' => 'required|string|in:Administrador,Doctor,Secretaria',
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
                        if (!empty($dato['clave'])) {
                            $existe->clave = $dato['clave'];
                        }
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
