<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App;
class ControllerUsuario extends Controller
{

    public function login($usuario,$clave)
    {

        $usuario = App\Usuario::where("usuario",$usuario)->where("clave",$clave)->get();
        return $usuario[0];
    
    }

   
}
