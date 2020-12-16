<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Firebase\JWT\JWT;
use App;
use DB;

class LoginController extends Controller
{

        public function Login(Request $post){

               /*return $resp = DB::table("usuarios")->where("nombre",$post->usuario)->where("clave",$post->clave)->first();
                if($resp){

                    $payload = array(
                        "iss" => "http://example.org",
                        "aud" => "http://example.com",
                        "iat" => 1356999524,
                        "nbf" => 1357000000
                    );
                    
              
                    $jwt = JWT::encode($payload,env("FIRMA_TOKEN"));



                }else{
                    
                    return "usuario no encontrado";
                }*/
        }

        public function GuardarUsuario($usuario,$clave,$nombre,$apellido,$roll){

                $usuario = new App\Usuario();
                $usuario->usuario = $usuario;
                $usuario->clave = $clave;
                $usuario->nombre = $nombre;
                $usuario->apellido = $apellido;
                $usuario->roll = $roll;
                $usuario->save();

        }

        public function ActualizarUsuario($usuario,$clave,$nombre,$apellido,$roll,$id){

            $usuario = App\Usuario::find($id);
            $usuario->usuario = $usuario;
            $usuario->clave = $clave;
            $usuario->nombre = $nombre;
            $usuario->apellido = $apellido;
            $usuario->roll = $roll;
            $usuario->save();


        }

}
