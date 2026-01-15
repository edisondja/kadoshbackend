<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SalarioDoctor;
use App\Doctor;
use DB;
use Carbon\Carbon;

class ControllerSalarioDoctor extends Controller
{
    /**
     * Listar todos los salarios de doctores
     */
    public function listarSalarios()
    {
        try {
            $salarios = SalarioDoctor::with('doctor')
                ->where('activo', true)
                ->orderBy('fecha_inicio', 'desc')
                ->get();
            
            return response()->json($salarios);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar salarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener salario activo de un doctor
     */
    public function obtenerSalarioDoctor($doctor_id)
    {
        try {
            $salario = SalarioDoctor::where('doctor_id', $doctor_id)
                ->where('activo', true)
                ->where(function($query) {
                    $query->whereNull('fecha_fin')
                          ->orWhere('fecha_fin', '>=', Carbon::now());
                })
                ->with('doctor')
                ->first();
            
            return response()->json($salario);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener salario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear o actualizar salario de doctor
     */
    public function guardarSalario(Request $request)
    {
        try {
            $request->validate([
                'doctor_id' => 'required|integer|exists:doctors,id',
                'salario' => 'required|numeric|min:0',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'nullable|date|after:fecha_inicio',
                'comentarios' => 'nullable|string'
            ]);

            DB::beginTransaction();

            // Si hay un salario activo, desactivarlo
            if ($request->id) {
                $salario = SalarioDoctor::findOrFail($request->id);
            } else {
                // Desactivar salarios anteriores del mismo doctor
                SalarioDoctor::where('doctor_id', $request->doctor_id)
                    ->where('activo', true)
                    ->update(['activo' => false, 'fecha_fin' => Carbon::now()]);
                
                $salario = new SalarioDoctor();
            }

            $salario->doctor_id = $request->doctor_id;
            $salario->salario = $request->salario;
            $salario->fecha_inicio = $request->fecha_inicio;
            $salario->fecha_fin = $request->fecha_fin;
            $salario->comentarios = $request->comentarios;
            $salario->activo = true;
            $salario->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salario guardado correctamente',
                'salario' => $salario->load('doctor')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al guardar salario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar doctores con sus salarios
     */
    public function listarDoctoresConSalarios()
    {
        try {
            $doctores = Doctor::with(['salarios' => function($query) {
                $query->where('activo', true)
                      ->orderBy('fecha_inicio', 'desc');
            }])->get();
            
            return response()->json($doctores);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar doctores',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar o desactivar salario
     */
    public function eliminarSalario($id)
    {
        try {
            $salario = SalarioDoctor::findOrFail($id);
            $salario->activo = false;
            $salario->fecha_fin = Carbon::now();
            $salario->save();

            return response()->json([
                'success' => true,
                'message' => 'Salario desactivado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar salario',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
