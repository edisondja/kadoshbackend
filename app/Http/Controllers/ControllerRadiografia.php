<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App;

class ControllerRadiografia extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function subir_documento(Request $request){
    // Validar campos obligatorios

        //\Log::info('Datos recibidos:', $request->all());

        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx,stl,obj,fbx,glb,gltf|max:10240', // hasta 10MB
            'comentarios' => 'required|string',
            'usuario_id' => 'required|exists:pacientes,id',
        ]);

        try {
            $archivoNombre = app(\App\Services\StorageQuotaService::class)
                ->storeBasename($request->file('image'), 'radiografias');

            $radiografia = new App\Radiografia();
            $radiografia->ruta_radiografia = $archivoNombre;
            $radiografia->id_usuario = $request->input('usuario_id');
            $radiografia->comentarios = $request->input('comentarios');
            $radiografia->save();

            return response()->json(['mensaje' => 'Archivo guardado con éxito.'], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
                'storage_full' => true,
            ], $e->getStatusCode());
        }
    }


    public function actualizar_documento(Request $data){

        #Codigo de actulizar el documento..
    
    }




    public function cargar_documentos($id){

    
        $radiografias =  App\Radiografia::where('id_usuario',$id)->get();
        return $radiografias;

    }
    

    public function eliminar_radiografia(Request $data){

        $radiografia =  App\Radiografia::find($data->input('id_radiografia'));
        if (!$radiografia) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }

        try {
            app(\App\Services\StorageQuotaService::class)->forget($radiografia->ruta_radiografia);
        } catch (\Throwable $e) {
            \Log::warning('No se pudo borrar archivo de storage: ' . $e->getMessage());
        }

        $radiografia->delete();

        return "Archivo eliminado con exito";
    }
    



}
