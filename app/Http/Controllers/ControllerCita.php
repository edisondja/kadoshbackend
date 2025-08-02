<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
class ControllerCita extends Controller
{
    // Lista todas las citas
    public function index()
    {
        return App\Cita::with('paciente', 'doctor')->orderBy('inicio', 'desc')->get();
    }


    

    // Crear nueva cita

  public function citas_paciente($paciente_id)
    {
        $citas = App\Cita::where('paciente_id', $paciente_id)
                    ->with('doctor') // Opcional: para incluir datos del doctor
                    ->orderBy('inicio', 'asc')
                    ->get();

        return response()->json($citas);
    }


    public function citas_doctor($doctor_id)
    {
        $citas = App\Cita::where('doctor_id', $doctor_id)
                    ->with('paciente') // Opcional: para incluir datos del paciente
                    ->orderBy('inicio', 'asc')
                    ->get();

        return response()->json($citas);
    }


    public function store(Request $request)
    {

        $request->validate([
            'motivo' => 'required|string',
            'inicio' => 'required|date',
            'fin' => 'required|date|after_or_equal:start',
            'paciente_id' => 'required|exists:pacientes,id',
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $cita = App\Cita::create($request->all());

        return response()->json([
            'status' => 'ok',
            'mensaje' => 'Cita registrada correctamente',
            'cita' => $cita
        ]);
    }

    // Ver una cita
    public function show($id)
    {
        $cita = Cita::with('paciente', 'doctor')->findOrFail($id);
        return $cita;
    }

   public function update(Request $request, $id){
       /* $request->validate([
            'motivo' => 'required|string',
            'inicio' => 'required|date',
            'fin' => 'required|date|after_or_equal:inicio',
            'paciente_id' => 'required|exists:pacientes,id',
            'doctor_id' => 'required|exists:doctors,id',
        ]);*/

        $cita = App\Cita::findOrFail($id);
        $cita->update($request->all());

        return response()->json([
            'status' => 'ok',
            'mensaje' => 'Cita actualizada correctamente',
            'cita' => $cita
        ]);
    }
    // Eliminar una cita
    public function destroy($id)
    {
        $cita = App\Cita::findOrFail($id);
        $cita->delete();

        return response()->json([
            'status' => 'ok',
            'mensaje' => 'Cita eliminada correctamente'
        ]);
    }
}


