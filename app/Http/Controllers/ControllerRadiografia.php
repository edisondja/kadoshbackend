<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Radiografia;

class ControllerRadiografia extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function subir_documento(Request $data){
        

    //    return $data;

        $archivo = $data->file('image')->store("public");
        $comentarios = $data->input('comentarios');
        $id_usuario = $data->input('usuario_id');
        
        $radio = new Radiografia();
        $radio->ruta_radiografia = $archivo[1];
        $radio->id_usuario=$id_usuario;
        $radio->comentarios =$comentarios;
        $radio->save();
        return "Rarchivo guardado con exito!";
    }

    public function actualizar_documento(Request $data){

        #Codigo de actulizar el documento..
    
    }




    public function cargar_documentos($id){

    
        $radiografias = Radiografia::where('id_usuario',$id)->get();
        return $radiografias;

    }
    

    public function eliminar_radiografia(Request $data){

        $radiografia = Radiografia::find($data->input('id_radiografia'));
        $radiografia->delete();
  
        return "Archivo eliminado con exito";


    }
    



}
