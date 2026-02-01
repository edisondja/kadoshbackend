<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Config;
use Illuminate\Support\Facades\Storage;

class ConfigController extends Controller
{
    public function index()
    {
        $configs = Config::all();
        return response()->json($configs->map(function($config) {
            return $this->appendStorageUrls($config);
        }));
    }

    public function store(Request $request)
    {
        try {
            // Validar campos requeridos
            $request->validate([
                'nombre' => 'required|string',
                'descripcion' => 'nullable|string',
                'email' => 'nullable|email',
                'telefono' => 'nullable|string',
                'dominio' => 'nullable|string',
                'ruta_logo' => 'nullable|file|image|max:2048',
                'ruta_favicon' => 'nullable|file|image|max:512',
                'clave_secreta' => 'nullable|string|max:255',
            ]);

            $data = $request->all();

            // Manejar archivos
            if ($request->hasFile('ruta_logo')) {
                $data['ruta_logo'] = $request->file('ruta_logo')->store('configs', 'public');
            } else {
                // Si no se envía logo, usar valor por defecto o null
                $data['ruta_logo'] = $data['ruta_logo'] ?? '';
            }

            if ($request->hasFile('ruta_favicon')) {
                $data['ruta_favicon'] = $request->file('ruta_favicon')->store('configs', 'public');
            } else {
                // Si no se envía favicon, usar valor por defecto o null
                $data['ruta_favicon'] = $data['ruta_favicon'] ?? '';
            }

            // Asegurar que los campos requeridos tengan valores por defecto si no se proporcionan (evitar NULL)
            $data['descripcion'] = $data['descripcion'] ?? '';
            $data['email'] = $data['email'] ?? '';
            $data['numero_empresa'] = $data['numero_empresa'] ?? '';
            $data['dominio'] = $data['dominio'] ?? '';
            $data['api_whatapps'] = $data['api_whatapps'] ?? '';
            $data['api_token_ws'] = $data['api_token_ws'] ?? '';
            $data['api_gmail'] = $data['api_gmail'] ?? '';
            $data['api_token_google'] = $data['api_token_google'] ?? '';
            $data['api_instagram'] = $data['api_instagram'] ?? '';
            $data['token_instagram'] = $data['token_instagram'] ?? '';
            $data['nombre_clinica'] = $data['nombre_clinica'] ?? '';
            $data['direccion_clinica'] = $data['direccion_clinica'] ?? '';
            $data['telefono_clinica'] = $data['telefono_clinica'] ?? '';
            $data['rnc_clinica'] = $data['rnc_clinica'] ?? '';
            $data['email_clinica'] = $data['email_clinica'] ?? '';
            $data['prefijo_factura'] = $data['prefijo_factura'] ?? '';
            $data['google_calendar_id'] = $data['google_calendar_id'] ?? '';

            // Manejar campos booleanos
            if (isset($data['usar_google_calendar'])) {
                $data['usar_google_calendar'] = filter_var($data['usar_google_calendar'], FILTER_VALIDATE_BOOLEAN);
            }

            $config = Config::create($data);

            return response()->json($this->appendStorageUrls($config), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => 'Por favor complete todos los campos requeridos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear configuración: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al guardar configuración',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $config = Config::findOrFail($id);
        return response()->json($this->appendStorageUrls($config));
    }

    public function update(Request $request, $id)
    {
        try {
            $config = Config::findOrFail($id);
            
            // Validar campos
            $request->validate([
                'nombre' => 'sometimes|required|string',
                'descripcion' => 'nullable|string',
                'email' => 'nullable|email',
                'telefono' => 'nullable|string',
                'dominio' => 'nullable|string',
                'ruta_logo' => 'nullable|file|image|max:2048',
                'ruta_favicon' => 'nullable|file|image|max:512',
                'clave_secreta' => 'nullable|string|max:255',
            ]);

            $data = $request->all();

            // Manejar campos booleanos
            if (isset($data['usar_google_calendar'])) {
                $data['usar_google_calendar'] = filter_var($data['usar_google_calendar'], FILTER_VALIDATE_BOOLEAN);
            }

            // Manejar archivos
            if ($request->hasFile('ruta_logo')) {
                // Eliminar logo anterior si existe
                if ($config->ruta_logo && !str_contains($config->ruta_logo, 'http')) {
                    try {
                        Storage::disk('public')->delete($config->ruta_logo);
                    } catch (\Exception $e) {
                        \Log::warning('No se pudo eliminar logo anterior: ' . $e->getMessage());
                    }
                }
                $data['ruta_logo'] = $request->file('ruta_logo')->store('configs', 'public');
            } else {
                // Si no se envía un nuevo archivo, mantener el existente
                unset($data['ruta_logo']);
            }

            if ($request->hasFile('ruta_favicon')) {
                // Eliminar favicon anterior si existe
                if ($config->ruta_favicon && !str_contains($config->ruta_favicon, 'http')) {
                    try {
                        Storage::disk('public')->delete($config->ruta_favicon);
                    } catch (\Exception $e) {
                        \Log::warning('No se pudo eliminar favicon anterior: ' . $e->getMessage());
                    }
                }
                $data['ruta_favicon'] = $request->file('ruta_favicon')->store('configs', 'public');
            } else {
                // Si no se envía un nuevo archivo, mantener el existente
                unset($data['ruta_favicon']);
            }

            $config->update($data);

            return response()->json($this->appendStorageUrls($config));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'message' => 'Por favor complete todos los campos requeridos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar configuración: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al actualizar configuración',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $config = Config::findOrFail($id);

        if ($config->ruta_logo && !str_contains($config->ruta_logo, 'http')) {
            Storage::disk('public')->delete($config->ruta_logo);
        }

        if ($config->ruta_favicon && !str_contains($config->ruta_favicon, 'http')) {
            Storage::disk('public')->delete($config->ruta_favicon);
        }

        $config->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }

    private function appendStorageUrls(Config $config)
    {
        if ($config->ruta_logo && !str_contains($config->ruta_logo, 'http')) {
            $config->ruta_logo = asset('storage/' . $config->ruta_logo);
        }

        if ($config->ruta_favicon && !str_contains($config->ruta_favicon, 'http')) {
            $config->ruta_favicon = asset('storage/' . $config->ruta_favicon);
        }

        return $config;
    }
}
