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


        return ["nombre"=>$usuario->nombre,"apellido"=>$usuario->apellido,"token"=>$jwt,"roll"=>$usuario->roll];
    
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

   
}
