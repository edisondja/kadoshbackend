<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FichaMedica;

class ControllerFichaMedica extends Controller
{
    

    // Métodos para manejar las fichas médicas

    public function show($id)
    {
        // Solo cargar una ficha médica por paciente
        $fichaMedica = FichaMedica::where('paciente_id', $id)->first();
        return response()->json($fichaMedica);
    }

    public function store(Request $request)
    {   

        
        //va;lidación
        $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'direccion' => 'nullable|string|max:255',
            'ocupacion' => 'nullable|string|max:255',
            'tratamiento_actual' => 'nullable|string|max:255',
            'tratamiento_detalle' => 'nullable|string',
            'enfermedades' => 'nullable|string',
            'medicamentos' => 'nullable|string',
            'tabaquismo' => 'nullable|string|max:255',
            'alcohol' => 'nullable|string|max:255',
            'otros_habitos' => 'nullable|string|max:255',
            'alergias' => 'nullable|string|max:255',
            'alergias_detalle' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);


        $data = $request->all();

        // Guardar o actualizar si ya existe ficha para el paciente
        $fichaMedica = FichaMedica::updateOrCreate(
            ['paciente_id' => $data['paciente_id']], // condición para buscar
            $data // campos a actualizar o crear
        );

        return response()->json($fichaMedica, 200);
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
