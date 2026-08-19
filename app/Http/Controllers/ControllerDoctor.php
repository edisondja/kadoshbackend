<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Mail;
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
        try {
            $doctors = DB::table('doctors')
                ->where('estado', true)
                ->orWhere('estado', 1)
                ->orderBy('id','desc')
                ->get();

            return $doctors;
        } catch (\Exception $e) {
            \Log::error('Error al cargar doctores: ' . $e->getMessage());
            // Si falla, intentar sin filtro de estado
            try {
                return DB::table('doctors')->orderBy('id','desc')->get();
            } catch (\Exception $e2) {
                \Log::error('Error al cargar doctores sin filtro: ' . $e2->getMessage());
                return [];
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     * Mantiene compatibilidad con rutas GET antiguas
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $nombre = null, $apellido = null, $cedula = null, $telefono = null)
    {
        // Si se llama desde ruta GET antigua (parámetros en URL)
        if ($nombre !== null && $apellido !== null && $cedula !== null && $telefono !== null) {
            try {
                $doctor = new App\Doctor();
                $doctor->nombre = $nombre;
                $doctor->apellido = $apellido;
                $doctor->dni = $cedula;
                $doctor->numero_telefono = $telefono;
                $doctor->estado = true; // Activo por defecto
                $doctor->save();
                return response()->json(['success' => true, 'doctor' => $doctor]);
            } catch (\Exception $e) {
                \Log::error('Error al crear doctor (GET): ' . $e->getMessage());
                return response()->json([
                    'error' => 'Error al crear doctor',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
        
        // Si se llama desde ruta POST nueva (Request body)
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'cedula' => 'required|string|max:255',
                'telefono' => 'required|string|max:255',
                'especialidad' => 'nullable|string|max:255',
                'sexo' => 'nullable|string|in:M,F',
                'correo_electronico' => 'nullable|email|max:191',
                'url_frontend' => 'nullable|string|max:512',
                'porcentaje_ingresos' => 'nullable|numeric|min:0|max:100',
            ]);

            $correoDoctor = trim((string) $request->input('correo_electronico', ''));

            $datosDoctor = [
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'dni' => $request->cedula,
                'numero_telefono' => $request->telefono,
                'especialidad' => $request->especialidad ?? null,
                'sexo' => $request->sexo ?? null,
                'correo_electronico' => $correoDoctor !== '' ? $correoDoctor : null,
                'estado' => true, // Activo por defecto
            ];

            if (\Schema::hasColumn('doctors', 'porcentaje_ingresos')) {
                $datosDoctor['porcentaje_ingresos'] = min(100, max(0, floatval($request->input('porcentaje_ingresos', 0))));
            }

            $doctor = App\Doctor::create($datosDoctor);

            $invitacion = $this->intentarInvitacionCorreoDoctor($request, $doctor, $correoDoctor);

            return response()->json([
                'success' => true,
                'message' => 'Doctor creado correctamente',
                'doctor' => $doctor,
                'invitacion_correo' => $invitacion,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear doctor (POST): ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al crear doctor',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
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
        try {
            // Buscar doctores activos por nombre
            $data = DB::table("doctors")
                ->where("nombre","like","%$nombre%")
                ->where(function($query) {
                    $query->where('estado', true)
                          ->orWhere('estado', 1);
                })
                ->take(20)
                ->get();

            return $data;
        } catch (\Exception $e) {
            \Log::error('Error al buscar doctor: ' . $e->getMessage());
            // Si falla, intentar sin filtro de estado
            try {
                return DB::table("doctors")
                    ->where("nombre","like","%$nombre%")
                    ->take(20)
                    ->get();
            } catch (\Exception $e2) {
                \Log::error('Error al buscar doctor sin filtro: ' . $e2->getMessage());
                return [];
            }
        }
    }

    public function cargar_doctor($id){
        $data = App\Doctor::find($id);
        if (!$data) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }
        return response()->json($this->appendFirmaUrl($data));
    }

    /**
     * Subir o reemplazar imagen de firma del doctor.
     */
    public function subirFirma(Request $request, $id)
    {
        try {
            if (!Schema::hasColumn('doctors', 'ruta_firma')) {
                return response()->json([
                    'error' => 'Columna no disponible',
                    'message' => 'Ejecute el SQL 2026_07_25_firma_doctor_documentos.sql'
                ], 500);
            }

            $request->validate([
                'ruta_firma' => 'required|file|image|max:2048',
            ]);

            $doctor = App\Doctor::findOrFail($id);

            if ($doctor->ruta_firma && strpos($doctor->ruta_firma, 'http') === false) {
                try {
                    Storage::disk('public')->delete($doctor->ruta_firma);
                } catch (\Exception $e) {
                    \Log::warning('No se pudo eliminar firma anterior: ' . $e->getMessage());
                }
            }

            $doctor->ruta_firma = $request->file('ruta_firma')->store('doctors/firmas', 'public');
            $doctor->save();

            return response()->json([
                'success' => true,
                'message' => 'Firma guardada correctamente',
                'doctor' => $this->appendFirmaUrl($doctor),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => 'Debe enviar una imagen válida (máx. 2MB)',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al subir firma doctor: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al subir firma',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar firma del doctor.
     */
    public function eliminarFirma($id)
    {
        try {
            $doctor = App\Doctor::findOrFail($id);

            if ($doctor->ruta_firma && strpos($doctor->ruta_firma, 'http') === false) {
                try {
                    Storage::disk('public')->delete($doctor->ruta_firma);
                } catch (\Exception $e) {
                    \Log::warning('No se pudo eliminar archivo de firma: ' . $e->getMessage());
                }
            }

            $doctor->ruta_firma = null;
            $doctor->save();

            return response()->json([
                'success' => true,
                'message' => 'Firma eliminada',
                'doctor' => $this->appendFirmaUrl($doctor),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar firma',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function appendFirmaUrl($doctor)
    {
        if ($doctor && $doctor->ruta_firma && strpos($doctor->ruta_firma, 'http') === false) {
            $doctor->ruta_firma_url = asset('storage/' . $doctor->ruta_firma);
        } elseif ($doctor && $doctor->ruta_firma) {
            $doctor->ruta_firma_url = $doctor->ruta_firma;
        } else {
            $doctor->ruta_firma_url = null;
        }
        return $doctor;
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
                    'especialidad' => 'nullable|string',
                    'sexo' => 'nullable|string|in:M,F',
                    'porcentaje_ingresos' => 'nullable|numeric|min:0|max:100',
                ]);

                $doctor = App\Doctor::findOrFail($id);
                $doctor->nombre = $request->nombre;
                $doctor->apellido = $request->apellido;
                $doctor->dni = $request->cedula;
                $doctor->numero_telefono = $request->telefono;
                $doctor->especialidad = $request->especialidad;
                if ($request->has('sexo')) {
                    $doctor->sexo = $request->sexo;
                }
                if ($request->has('porcentaje_ingresos')) {
                    $doctor->porcentaje_ingresos = min(100, max(0, floatval($request->porcentaje_ingresos)));
                }
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
            $doctorId = $request->id_doctor;
            
            if (!$doctorId) {
                return response()->json([
                    'error' => 'Error al desactivar doctor',
                    'message' => 'ID de doctor no proporcionado'
                ], 400);
            }

            $doctor = App\Doctor::findOrFail($doctorId);
            $doctor->estado = false;
            $doctor->save();

            return response()->json([
                'success' => true,
                'message' => 'Doctor desactivado correctamente',
                'doctor' => $doctor
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al desactivar doctor: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al desactivar doctor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function activar_doctor(Request $request)
    {
        try {
            $doctorId = $request->id_doctor;
            
            if (!$doctorId) {
                return response()->json([
                    'error' => 'Error al activar doctor',
                    'message' => 'ID de doctor no proporcionado'
                ], 400);
            }

            $doctor = App\Doctor::findOrFail($doctorId);
            $doctor->estado = true;
            $doctor->save();

            return response()->json([
                'success' => true,
                'message' => 'Doctor activado correctamente',
                'doctor' => $doctor
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al activar doctor: ' . $e->getMessage());
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
        try {
            $doctors = DB::table('doctors')->orderBy('id','desc')->get();
            return $doctors;
        } catch (\Exception $e) {
            \Log::error('Error en indexAll: ' . $e->getMessage());
            return [];
        }
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

    private function resolverUrlFrontendRegistro(Request $request)
    {
        $u = trim((string) $request->input('url_frontend', ''));
        if ($u !== '' && preg_match('#^https?://#i', $u)) {
            return rtrim($u, '/');
        }
        $env = rtrim((string) env('APP_FRONTEND_URL', ''), '/');
        if ($env !== '') {
            return $env;
        }
        $cfg = App\Config::first();
        if ($cfg && !empty(trim((string) ($cfg->dominio ?? '')))) {
            $d = trim($cfg->dominio);
            if (!preg_match('#^https?://#i', $d)) {
                $d = 'https://'.$d;
            }

            return rtrim($d, '/');
        }

        return null;
    }

    /**
     * Crea token de invitación y envía correo al odontólogo (POST crear doctor).
     *
     * @param  string  $correoDoctor
     * @return array{enviada: bool, motivo?: string, expira?: string}
     */
    private function intentarInvitacionCorreoDoctor(Request $request, $doctor, $correoDoctor)
    {
        $correoDoctor = trim((string) $correoDoctor);
        if ($correoDoctor === '' || !filter_var($correoDoctor, FILTER_VALIDATE_EMAIL)) {
            return ['enviada' => false, 'motivo' => 'sin_correo'];
        }

        $base = $this->resolverUrlFrontendRegistro($request);
        if (!$base) {
            return ['enviada' => false, 'motivo' => 'sin_url_frontend'];
        }

        $config = App\Config::first();
        $nombreClinica = $config && !empty(trim((string) ($config->nombre_clinica ?? '')))
            ? trim($config->nombre_clinica)
            : (($config && !empty($config->nombre)) ? $config->nombre : 'la clínica');

        $nombreDoctor = trim($doctor->nombre.' '.$doctor->apellido);
        $dias = 14;

        $token = Str::random(48);
        $expiresAt = Carbon::now()->addDays($dias);

        try {
            DB::table('doctor_invitaciones')->insert([
                'token' => $token,
                'id_doctor' => (int) $doctor->id,
                'expires_at' => $expiresAt,
                'used_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('doctor_invitaciones insert: '.$e->getMessage());

            return ['enviada' => false, 'motivo' => 'error_invitacion'];
        }

        $enlace = $base.'/registro_doctor/'.$token;

        try {
            Mail::send('emails.invitacion_doctor', [
                'nombreClinica' => $nombreClinica,
                'nombreDoctor' => $nombreDoctor,
                'enlaceRegistro' => $enlace,
                'diasValidez' => $dias,
            ], function ($m) use ($correoDoctor, $nombreClinica) {
                $m->to($correoDoctor)->subject('Acceso al sistema - '.$nombreClinica);
            });
        } catch (\Exception $e) {
            \Log::warning('No se pudo enviar correo invitación doctor: '.$e->getMessage());

            return ['enviada' => false, 'motivo' => 'error_correo'];
        }

        return ['enviada' => true, 'expira' => $expiresAt->toIso8601String()];
    }
}
