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


        return ["nombre"=>$usuario->nombre,"apellido"=>$usuario->apellido,"token"=>$jwt];
    
    }

   
}
