<?php

namespace App\Http\Controllers;

use App;
use Carbon\Carbon;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class DoctorInvitacionController extends Controller
{
    /**
     * Comprueba si el enlace sigue vigente (público).
     */
    public function verificar($token)
    {
        try {
            $row = DB::table('doctor_invitaciones')->where('token', $token)->first();
        } catch (QueryException $e) {
            $sql = $e->getMessage();
            if (strpos($sql, 'Base table') !== false || strpos($sql, "doesn't exist") !== false) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'El módulo de invitaciones no está instalado (falta migración).',
                ], 503);
            }
            throw $e;
        }

        if (!$row) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Este enlace no existe o ya no es válido.',
            ], 410);
        }

        if ($row->used_at !== null) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Este enlace ya fue usado.',
            ], 410);
        }

        if ($row->expires_at && Carbon::parse($row->expires_at)->isPast()) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Este enlace expiró. Solicite uno nuevo a la clínica.',
            ], 410);
        }

        $doctor = App\Doctor::find($row->id_doctor);
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Doctor no encontrado.',
            ], 410);
        }

        if (!empty($doctor->id_usuario)) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Este doctor ya tiene usuario de acceso.',
            ], 410);
        }

        $config = App\Config::first();
        $nombreClinica = $config && !empty($config->nombre_clinica)
            ? $config->nombre_clinica
            : (($config && $config->nombre) ? $config->nombre : 'la clínica');

        return response()->json([
            'success' => true,
            'valid' => true,
            'nombre_clinica' => $nombreClinica,
            'nombre' => trim((string) ($doctor->nombre ?? '')),
            'apellido' => trim((string) ($doctor->apellido ?? '')),
        ], 200);
    }

    /**
     * Crea usuario (rol Odontologo) y vincula al doctor; invalida el enlace.
     */
    public function registrar(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:80',
            'usuario' => 'required|string|min:3|max:191',
            'clave' => 'required|string|min:6|max:191|confirmed',
        ], [
            'clave.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $token = $request->input('token');
        $login = trim($request->input('usuario'));

        try {
            DB::beginTransaction();

            $row = DB::table('doctor_invitaciones')->where('token', $token)->lockForUpdate()->first();

            if (!$row) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Enlace no válido.'], 410);
            }

            if ($row->used_at !== null) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Este enlace ya fue usado.'], 410);
            }

            if ($row->expires_at && Carbon::parse($row->expires_at)->isPast()) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Este enlace expiró.'], 410);
            }

            $doctor = App\Doctor::where('id', $row->id_doctor)->lockForUpdate()->first();
            if (!$doctor) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Doctor no encontrado.'], 410);
            }

            if (!empty($doctor->id_usuario)) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Este doctor ya tiene usuario de acceso.'], 409);
            }

            if (App\Usuario::where('usuario', $login)->exists()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Ese nombre de usuario ya está en uso. Elija otro.',
                ], 422);
            }

            $usuario = new App\Usuario();
            $usuario->usuario = $login;
            $usuario->clave = $request->input('clave');
            $usuario->nombre = $doctor->nombre;
            $usuario->apellido = $doctor->apellido;
            $usuario->roll = 'Odontologo';
            $usuario->save();

            $doctor->id_usuario = $usuario->id;
            $doctor->save();

            DB::table('doctor_invitaciones')->where('id', $row->id)->update([
                'used_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro completado. Ya puede iniciar sesión con su usuario y contraseña.',
            ], 200);
        } catch (QueryException $e) {
            DB::rollBack();
            $sql = $e->getMessage();
            if (strpos($sql, 'Base table') !== false || strpos($sql, "doesn't exist") !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta la tabla doctor_invitaciones. Ejecute php artisan migrate en esta base de datos.',
                ], 503);
            }
            \Log::error('invitacion_doctor registrar: '.$sql);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar el registro. Intente de nuevo.',
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('invitacion_doctor registrar: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar el registro. Intente de nuevo.',
            ], 500);
        }
    }
}
