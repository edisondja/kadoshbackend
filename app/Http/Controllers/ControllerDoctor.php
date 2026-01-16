<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App;

class ControllerDoctor extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Filtrar solo doctores activos (estado = true o 1)
        $doctors = DB::table('doctors')
            ->where(function($query) {
                $query->where('estado', true)
                      ->orWhere('estado', 1);
            })
            ->orderBy('id','desc')
            ->get();

        return $doctors;
    }

    /**
     * Show the form for creating a new resource.
     * Mantiene compatibilidad con rutas GET antiguas
     *
     * @return \Illuminate\Http\Response
     */
    public function create($nombre = null, $apellido = null, $cedula = null, $telefono = null)
    {
        // Si se llama desde ruta GET antigua (parámetros en URL)
        if ($nombre !== null && $apellido !== null && $cedula !== null && $telefono !== null) {
                $doctor = new App\Doctor();
                $doctor->nombre = $nombre;
                $doctor->apellido = $apellido;
                $doctor->dni = $cedula;
                $doctor->numero_telefono = $telefono;
                $doctor->estado = true; // Activo por defecto
                $doctor->save();
            return response()->json(['success' => true, 'doctor' => $doctor]);
        }
        
        // Si se llama desde ruta POST nueva (Request body)
        $request = request();
        if ($request->has('nombre')) {
            try {
                $request->validate([
                    'nombre' => 'required|string',
                    'apellido' => 'required|string',
                    'cedula' => 'required|string',
                    'telefono' => 'required|string',
                    'especialidad' => 'nullable|string'
                ]);

                $doctor = new App\Doctor();
                $doctor->nombre = $request->nombre;
                $doctor->apellido = $request->apellido;
                $doctor->dni = $request->cedula;
                $doctor->numero_telefono = $request->telefono;
                $doctor->especialidad = $request->especialidad;
                $doctor->estado = true; // Activo por defecto
                $doctor->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Doctor creado correctamente',
                    'doctor' => $doctor
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Error al crear doctor',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
        
        return response()->json(['error' => 'Parámetros inválidos'], 400);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function buscando_doctor($nombre)
    {
        // Filtrar solo doctores activos
        $data = DB::table("doctors")
            ->where("nombre","like","%$nombre%")
            ->where(function($query) {
                $query->where('estado', true)
                      ->orWhere('estado', 1);
            })
            ->take(20)
            ->get();

        return $data;
    }

    public function cargar_doctor($id){
    
        
        $data = App\Doctor::find($id);
        return $data;
    }

    /**
     * Show the form for editing the specified resource.
     * Mantiene compatibilidad con rutas GET antiguas
     *
     * @param  int  $id 
     * @return \Illuminate\Http\Response
     */
    public function edit($nombre = null, $apellido = null, $cedula = null, $telefono = null, $id = null)
    {
        // Si se llama desde ruta GET antigua (parámetros en URL)
        if ($nombre !== null && $apellido !== null && $cedula !== null && $telefono !== null && $id !== null) {
            $doctor = App\Doctor::find($id);
            if ($doctor) {
                $doctor->nombre = $nombre;
                $doctor->apellido = $apellido;
                $doctor->dni = $cedula;
                $doctor->numero_telefono = $telefono;
                $doctor->save();
            }
            return response()->json(['success' => true, 'doctor' => $doctor]);
        }
        
        // Si se llama desde ruta PUT nueva (Request body)
        $request = request();
        if ($request->has('nombre') && $id !== null) {
            try {
                $request->validate([
                    'nombre' => 'required|string',
                    'apellido' => 'required|string',
                    'cedula' => 'required|string',
                    'telefono' => 'required|string',
                    'especialidad' => 'nullable|string'
                ]);

                $doctor = App\Doctor::findOrFail($id);
                $doctor->nombre = $request->nombre;
                $doctor->apellido = $request->apellido;
                $doctor->dni = $request->cedula;
                $doctor->numero_telefono = $request->telefono;
                $doctor->especialidad = $request->especialidad;
                $doctor->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Doctor actualizado correctamente',
                    'doctor' => $doctor
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Error al actualizar doctor',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
        
        return response()->json(['error' => 'Parámetros inválidos'], 400);
    }


    public function desactivar_doctor(Request $request)
    {
        try {
            $doctor = App\Doctor::findOrFail($request->id_doctor);
            $doctor->estado = false;
            $doctor->save();

            return response()->json([
                'success' => true,
                'message' => 'Doctor desactivado correctamente',
                'doctor' => $doctor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al desactivar doctor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function activar_doctor(Request $request)
    {
        try {
            $doctor = App\Doctor::findOrFail($request->id_doctor);
            $doctor->estado = true;
            $doctor->save();

            return response()->json([
                'success' => true,
                'message' => 'Doctor activado correctamente',
                'doctor' => $doctor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al activar doctor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los doctores (incluyendo inactivos) - Solo para administración
     */
    public function indexAll()
    {
        $doctors = DB::table('doctors')->orderBy('id','desc')->get();
        return $doctors;
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    
           $doctor = App\Doctor::find($id);
           $doctor->delete();

           return "doctor eliminado";
           

    }
}
