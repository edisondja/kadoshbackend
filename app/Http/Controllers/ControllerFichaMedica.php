<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FichaMedica;

class ControllerFichaMedica extends Controller
{
    

    // Métodos para manejar las fichas médicas

    public function show($id)
    {
        // Lógica para mostrar una ficha médica específica
        $fichaMedica = FichaMedica::find($id);
        return response()->json($fichaMedica);
    }

    public function update($id, $data)
    {
        // Lógica para actualizar una ficha médica existente
        $fichaMedica = FichaMedica::find($id);
        $fichaMedica->update($data);
        return response()->json($fichaMedica);
    }

    public function destroy($id)
    {
        // Lógica para eliminar una ficha médica
        $fichaMedica = FichaMedica::find($id);
        $fichaMedica->delete();
        return response()->json(null, 204);
    }

    public function buscar_fichas($nombre)
    {
        // Lógica para buscar fichas médicas por nombre del paciente
        $fichas = FichaMedica::whereHas('paciente', function($query) use ($nombre) {
            $query->where('nombre', 'like', '%' . $nombre . '%');
        })->get();

        return response()->json($fichas);
    }

}
