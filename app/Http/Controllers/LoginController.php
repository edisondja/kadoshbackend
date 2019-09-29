<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use DB;

class LoginController extends Controller
{

        public function Login(Request $post){

                $resp = DB::table("usuarios")->where("nombre",$post->usuario)->where("clave",$post->clave)->get();
                if($resp){

                    return $resp;
                }else{
                    
                    return "usuario no encontrado";
                }
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
