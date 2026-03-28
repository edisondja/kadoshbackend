<?php

namespace App\Http\Controllers;

use App;
use Carbon\Carbon;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PacienteInvitacionController extends Controller
{
    /**
     * Crea un enlace de un solo uso (se invalida al registrarse el paciente).
     */
    public function crear(Request $request)
    {
        $request->validate([
            'id_doctor' => 'required|integer|min:1',
            'telefono_destino' => 'nullable|string|max:40',
        ], [
            'id_doctor.required' => 'Seleccione el doctor asignado.',
        ]);

        $doctor = App\Doctor::find($request->id_doctor);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor no válido.'], 422);
        }

        $token = Str::random(48);
        $expiresAt = Carbon::now()->addDays(30);

        try {
            DB::table('paciente_invitaciones')->insert([
                'token' => $token,
                'id_doctor' => (int) $request->id_doctor,
                'telefono_destino' => $request->telefono_destino ? trim($request->telefono_destino) : null,
                'expires_at' => $expiresAt,
                'used_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (QueryException $e) {
            $sql = $e->getMessage();
            if (strpos($sql, 'Base table') !== false || strpos($sql, "doesn't exist") !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta la tabla paciente_invitaciones. Ejecute php artisan migrate en la base de datos de esta clínica (tenant).',
                ], 503);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear la invitación (error de base de datos).',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ], 201);
    }

    /**
     * Comprueba si el enlace sigue vigente (público).
     */
    public function verificar($token)
    {
        try {
            $row = DB::table('paciente_invitaciones')->where('token', $token)->first();
        } catch (QueryException $e) {
            $sql = $e->getMessage();
            if (strpos($sql, 'Base table') !== false || strpos($sql, "doesn't exist") !== false) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'El módulo de invitaciones no está instalado en el servidor (falta migración).',
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
                'message' => 'Este enlace ya fue usado. Solicite una nueva invitación a la clínica.',
            ], 410);
        }

        if ($row->expires_at && Carbon::parse($row->expires_at)->isPast()) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Este enlace expiró. Solicite una nueva invitación.',
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
            'id_doctor' => (int) $row->id_doctor,
        ], 200);
    }

    /**
     * Registro del paciente con token; invalida el enlace al terminar.
     */
    public function registrar(Request $request)
    {
        // JSON/envíos del front suelen mandar '' en fecha; nullable|date falla con cadena vacía.
        if (!$request->filled('fecha_nacimiento')) {
            $request->merge(['fecha_nacimiento' => null]);
        }

        $request->validate([
            'token' => 'required|string|max:80',
            'nombre' => 'required|string|max:191',
            'apellido' => 'required|string|max:191',
            'telefono' => 'required|string|max:191',
            'cedula' => 'nullable|string|max:191',
            'correo_electronico' => 'nullable|string|max:191',
            'fecha_nacimiento' => 'nullable|date',
            'sexo' => 'required|string|max:10',
            'nombre_tutor' => 'nullable|string|max:191',
            'foto_paciente' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ], [
            'foto_paciente.image' => 'La foto debe ser una imagen (JPG, PNG o GIF).',
            'foto_paciente.max' => 'La foto no puede superar 5 MB.',
        ]);

        $token = $request->input('token');

        try {
            DB::beginTransaction();

            $row = DB::table('paciente_invitaciones')->where('token', $token)->lockForUpdate()->first();

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

            $paciente = new App\Paciente();
            $paciente->nombre = $request->nombre;
            $paciente->apellido = $request->apellido;
            $paciente->telefono = $request->telefono;
            $paciente->id_doctor = (int) $row->id_doctor;
            $paciente->cedula = trim((string) ($request->cedula ?? '')) ?: '';
            $paciente->correo_electronico = trim((string) ($request->correo_electronico ?? '')) ?: null;
            $paciente->fecha_de_ingreso = date('Y-m-d H:i:s');
            $paciente->fecha_nacimiento = !empty(trim((string) ($request->fecha_nacimiento ?? '')))
                ? $request->fecha_nacimiento
                : '1900-01-01';

            if ($request->hasFile('foto_paciente')) {
                $storedPath = $request->file('foto_paciente')->store('public');
                $paciente->foto_paciente = basename(str_replace('\\', '/', $storedPath));
            } else {
                $paciente->foto_paciente = '';
            }

            $paciente->nombre_tutor = $request->nombre_tutor ?? '';
            $paciente->sexo = $request->sexo;
            $paciente->save();

            DB::table('paciente_invitaciones')->where('id', $row->id)->update([
                'used_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro completado. Ya puede cerrar esta página.',
                'id_paciente' => $paciente->id,
            ], 200);
        } catch (QueryException $e) {
            DB::rollBack();
            $sql = $e->getMessage();
            if (strpos($sql, 'Base table') !== false || strpos($sql, "doesn't exist") !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta la tabla paciente_invitaciones. Ejecute php artisan migrate en esta base de datos.',
                ], 503);
            }
            \Log::error('invitacion_paciente registrar: '.$sql);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar el registro. Intente de nuevo o contacte a la clínica.',
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('invitacion_paciente registrar: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar el registro. Intente de nuevo o contacte a la clínica.',
            ], 500);
        }
    }
}
