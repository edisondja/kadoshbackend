<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Especialidad;
use DB;

class ControllerEspecialidad extends Controller
{
    /**
     * Listar todas las especialidades
     */
    public function listarEspecialidades()
    {
        try {
            $especialidades = Especialidad::where('estado', true)
                ->orderBy('nombre', 'asc')
                ->get();
            
            return response()->json($especialidades);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar especialidades',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todas las especialidades (incluyendo inactivas)
     */
    public function listarTodasEspecialidades()
    {
        try {
            $especialidades = Especialidad::orderBy('nombre', 'asc')->get();
            return response()->json($especialidades);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar especialidades',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una especialidad específica
     */
    public function obtenerEspecialidad($id)
    {
        try {
            $especialidad = Especialidad::findOrFail($id);
            return response()->json($especialidad);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener especialidad',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Crear una nueva especialidad
     */
    public function crearEspecialidad(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255|unique:especialidades,nombre',
                'descripcion' => 'nullable|string',
                'estado' => 'nullable|boolean'
            ]);

            $especialidad = Especialidad::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? null,
                'estado' => $request->estado ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Especialidad creada correctamente',
                'especialidad' => $especialidad
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => 'El nombre de la especialidad ya existe o es inválido',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear especialidad',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una especialidad
     */
    public function actualizarEspecialidad(Request $request, $id)
    {
        try {
            $especialidad = Especialidad::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|max:255|unique:especialidades,nombre,' . $id,
                'descripcion' => 'nullable|string',
                'estado' => 'nullable|boolean'
            ]);

            $especialidad->nombre = $request->nombre;
            $especialidad->descripcion = $request->descripcion ?? null;
            if ($request->has('estado')) {
                $especialidad->estado = $request->estado;
            }
            $especialidad->save();

            return response()->json([
                'success' => true,
                'message' => 'Especialidad actualizada correctamente',
                'especialidad' => $especialidad
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => 'El nombre de la especialidad ya existe o es inválido',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar especialidad',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (desactivar) una especialidad
     */
    public function eliminarEspecialidad($id)
    {
        try {
            $especialidad = Especialidad::findOrFail($id);
            
            // Verificar si hay doctores usando esta especialidad
            $doctoresConEspecialidad = DB::table('doctors')
                ->where('especialidad', $especialidad->nombre)
                ->count();

            if ($doctoresConEspecialidad > 0) {
                // En lugar de eliminar, desactivar
                $especialidad->estado = false;
                $especialidad->save();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Especialidad desactivada (hay doctores que la usan)',
                    'especialidad' => $especialidad
                ]);
            }

            // Si no hay doctores, eliminar físicamente
            $especialidad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Especialidad eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar especialidad',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar una especialidad
     */
    public function activarEspecialidad($id)
    {
        try {
            $especialidad = Especialidad::findOrFail($id);
            $especialidad->estado = true;
            $especialidad->save();

            return response()->json([
                'success' => true,
                'message' => 'Especialidad activada correctamente',
                'especialidad' => $especialidad
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al activar especialidad',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
