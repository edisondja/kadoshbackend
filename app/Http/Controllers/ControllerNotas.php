<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Nota;


class ControllerNotas extends Controller{

    

    public function agregar_nota(Request $data){

  

        $nota = new Nota();
        $nota->descripcion = $data->nota;
        $nota->id_paciente = $data->id_paciente;
        $nota->save();

    }

    public function actualizar_nota(Request $data){


      // return $data;
        $nota = Nota::find($data->id_nota);
        $nota->descripcion = $data->descripcion;
        $nota->save();

    }


    public function eliminar_nota(Request $data){
 
        $nota = Nota::find($data->input('nota_id'));
        $nota->delete();

    }


    public function cargar_notas($id_paciente){

            $notas = Nota::where('id_paciente',$id_paciente)->orderBy('id','desc')->get();
            return $notas;

    }


    public function ver_nota($id_nota){

        $nota = Nota::find($id_nota);
        return $nota;


    }




}   
