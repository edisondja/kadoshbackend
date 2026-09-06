<?php

namespace App\Http\Controllers;

use App\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ControllerTenant extends Controller
{
    private function columnasTenants()
    {
        try {
            return Schema::connection('mysql')->getColumnListing('tenants');
        } catch (\Exception $e) {
            return [];
        }
    }

    private function filtrarPayload(array $data)
    {
        $cols = $this->columnasTenants();
        if (empty($cols)) {
            // Si no se pueden leer columnas, quitar campos de deploy por seguridad
            unset($data['dominio'], $data['document_root'], $data['api_url'], $data['vhost_enabled'], $data['ultimo_deploy_at']);
            return $data;
        }
        return array_intersect_key($data, array_flip($cols));
    }

    private function normalizarEntrada(Request $request)
    {
        $data = $request->all();

        foreach ([
            'nombre', 'subdominio', 'database_name', 'dominio', 'document_root', 'api_url',
            'contacto_nombre', 'contacto_email', 'contacto_telefono', 'notas', 'fecha_vencimiento'
        ] as $k) {
            if (!array_key_exists($k, $data)) {
                continue;
            }
            if (is_string($data[$k])) {
                $data[$k] = trim($data[$k]);
            }
            if ($data[$k] === '' || $data[$k] === 'null') {
                $data[$k] = null;
            }
        }

        if (!empty($data['subdominio'])) {
            $data['subdominio'] = strtolower(preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $data['subdominio'])));
        }
        if (!empty($data['dominio'])) {
            $data['dominio'] = strtolower((string) $data['dominio']);
        }

        if (array_key_exists('activo', $data)) {
            $v = $data['activo'];
            if (is_bool($v)) {
                $data['activo'] = $v;
            } elseif ($v === 1 || $v === '1' || $v === 'true' || $v === 'on') {
                $data['activo'] = true;
            } elseif ($v === 0 || $v === '0' || $v === 'false' || $v === 'off') {
                $data['activo'] = false;
            } else {
                $data['activo'] = true;
            }
        }

        if (array_key_exists('bloqueado', $data)) {
            $v = $data['bloqueado'];
            if (is_bool($v)) {
                $data['bloqueado'] = $v;
            } elseif ($v === 1 || $v === '1' || $v === 'true' || $v === 'on') {
                $data['bloqueado'] = true;
            } else {
                $data['bloqueado'] = false;
            }
        }

        return $data;
    }

    private function enriquecer(Tenant $tenant)
    {
        $tenant->dias_restantes = $tenant->diasRestantes();
        $tenant->estado = $tenant->estado;
        $tenant->esta_vencido = $tenant->estaVencido();
        $tenant->puede_acceder = $tenant->puedeAcceder();
        return $tenant;
    }

    private function respuestaValidacion($validator)
    {
        $errors = $validator->errors()->toArray();
        $flat = [];
        foreach ($errors as $msgs) {
            foreach ((array) $msgs as $m) {
                $flat[] = $m;
            }
        }
        return response()->json([
            'error' => 'Error de validación: ' . implode(' ', $flat),
            'errors' => $errors,
            'message' => implode(' ', $flat),
        ], 422);
    }

    public function index()
    {
        try {
            $tenants = Tenant::orderBy('created_at', 'desc')->get()->map(function ($tenant) {
                return $this->enriquecer($tenant);
            });
            return response()->json($tenants, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar tenants',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $this->normalizarEntrada($request);

        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:255',
            'subdominio' => 'required|string|max:100',
            'database_name' => 'required|string|max:255',
            'dominio' => 'nullable|string|max:255',
            'document_root' => 'nullable|string|max:500',
            'api_url' => 'nullable|string|max:500',
            'fecha_vencimiento' => 'nullable|date',
            'activo' => 'nullable|boolean',
            'bloqueado' => 'nullable|boolean',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_email' => 'nullable|email|max:255',
            'contacto_telefono' => 'nullable|string|max:50',
            'notas' => 'nullable|string'
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'subdominio.required' => 'El subdominio es obligatorio.',
            'database_name.required' => 'El nombre de base de datos es obligatorio.',
            'contacto_email.email' => 'El email de contacto no es válido.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
        ]);

        if ($validator->fails()) {
            return $this->respuestaValidacion($validator);
        }

        if (Tenant::where('subdominio', $data['subdominio'])->exists()) {
            return response()->json([
                'error' => 'Error de validación: Ese subdominio ya está registrado.',
                'errors' => ['subdominio' => ['Ese subdominio ya está registrado.']],
                'message' => 'Ese subdominio ya está registrado.',
            ], 422);
        }

        try {
            $subdominio = $data['subdominio'];
            $dominio = !empty($data['dominio']) ? $data['dominio'] : ($subdominio . '.odontoed.com');
            $docroot = !empty($data['document_root'])
                ? rtrim($data['document_root'], '/')
                : '/var/www/' . $dominio . '/public_html';
            $apiUrl = !empty($data['api_url'])
                ? rtrim($data['api_url'], '/')
                : 'https://' . $dominio;

            $payload = [
                'nombre' => $data['nombre'],
                'subdominio' => $subdominio,
                'dominio' => $dominio,
                'database_name' => $data['database_name'],
                'document_root' => $docroot,
                'api_url' => $apiUrl,
                'fecha_vencimiento' => isset($data['fecha_vencimiento']) ? $data['fecha_vencimiento'] : null,
                'activo' => array_key_exists('activo', $data) ? (bool) $data['activo'] : true,
                'bloqueado' => array_key_exists('bloqueado', $data) ? (bool) $data['bloqueado'] : false,
                'contacto_nombre' => isset($data['contacto_nombre']) ? $data['contacto_nombre'] : null,
                'contacto_email' => isset($data['contacto_email']) ? $data['contacto_email'] : null,
                'contacto_telefono' => isset($data['contacto_telefono']) ? $data['contacto_telefono'] : null,
                'notas' => isset($data['notas']) ? $data['notas'] : null,
            ];

            $payload = $this->filtrarPayload($payload);
            $tenant = Tenant::create($payload);

            return response()->json([
                'message' => 'Tenant creado exitosamente',
                'tenant' => $this->enriquecer($tenant)
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Tenant store: ' . $e->getMessage());
            $msg = $e->getMessage();
            if (stripos($msg, 'Unknown column') !== false) {
                $msg = 'Faltan columnas en la tabla tenants. Ejecute en BD clinica: database/sql/2026_09_06_tenants_deploy_fields.sql';
            }
            return response()->json([
                'error' => 'Error al crear tenant',
                'message' => $msg
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no encontrado'], 404);
        }

        $data = $this->normalizarEntrada($request);

        $validator = Validator::make($data, [
            'nombre' => 'sometimes|required|string|max:255',
            'subdominio' => 'sometimes|required|string|max:100',
            'database_name' => 'sometimes|required|string|max:255',
            'dominio' => 'nullable|string|max:255',
            'document_root' => 'nullable|string|max:500',
            'api_url' => 'nullable|string|max:500',
            'fecha_vencimiento' => 'nullable|date',
            'activo' => 'nullable|boolean',
            'bloqueado' => 'nullable|boolean',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_email' => 'nullable|email|max:255',
            'contacto_telefono' => 'nullable|string|max:50',
            'notas' => 'nullable|string'
        ], [
            'contacto_email.email' => 'El email de contacto no es válido.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
        ]);

        if ($validator->fails()) {
            return $this->respuestaValidacion($validator);
        }

        if (!empty($data['subdominio'])) {
            $exists = Tenant::where('subdominio', $data['subdominio'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'error' => 'Error de validación: Ese subdominio ya está registrado.',
                    'errors' => ['subdominio' => ['Ese subdominio ya está registrado.']],
                    'message' => 'Ese subdominio ya está registrado.',
                ], 422);
            }
        }

        try {
            $allowed = [
                'nombre', 'subdominio', 'dominio', 'database_name', 'document_root', 'api_url',
                'fecha_vencimiento', 'activo', 'bloqueado',
                'contacto_nombre', 'contacto_email', 'contacto_telefono', 'notas'
            ];
            $payload = [];
            foreach ($allowed as $key) {
                if (array_key_exists($key, $data)) {
                    $payload[$key] = $data[$key];
                }
            }
            if (!empty($payload['document_root'])) {
                $payload['document_root'] = rtrim($payload['document_root'], '/');
            }
            if (!empty($payload['api_url'])) {
                $payload['api_url'] = rtrim($payload['api_url'], '/');
            }

            $payload = $this->filtrarPayload($payload);
            $tenant->update($payload);
            $tenant->refresh();

            return response()->json([
                'message' => 'Tenant actualizado exitosamente',
                'tenant' => $this->enriquecer($tenant)
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Tenant update: ' . $e->getMessage());
            $msg = $e->getMessage();
            if (stripos($msg, 'Unknown column') !== false) {
                $msg = 'Faltan columnas en la tabla tenants. Ejecute en BD clinica: database/sql/2026_09_06_tenants_deploy_fields.sql';
            }
            return response()->json([
                'error' => 'Error al actualizar tenant',
                'message' => $msg
            ], 500);
        }
    }

    public function destroy($id)
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no encontrado'], 404);
        }
        try {
            $tenant->delete();
            return response()->json(['message' => 'Tenant eliminado exitosamente'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar tenant',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['error' => 'Tenant no encontrado'], 404);
            }
            return response()->json($this->enriquecer($tenant), 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar tenant',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verificarEstado($subdominio)
    {
        try {
            $tenant = Tenant::where('subdominio', $subdominio)->first();
            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant no encontrado',
                    'puede_acceder' => false
                ], 404);
            }
            return response()->json([
                'tenant' => $tenant,
                'puede_acceder' => $tenant->puedeAcceder(),
                'esta_vencido' => $tenant->estaVencido(),
                'dias_restantes' => $tenant->diasRestantes(),
                'estado' => $tenant->estado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al verificar estado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function activar($id)
    {
        return $this->cambiarEstado($id, true, null, 'Tenant activado. Ya puede acceder al sistema.');
    }

    public function desactivar($id)
    {
        return $this->cambiarEstado($id, false, null, 'Tenant desactivado. No podrá acceder al sistema.');
    }

    public function bloquear($id)
    {
        return $this->cambiarEstado($id, null, true, 'Tenant bloqueado. Acceso denegado.');
    }

    public function desbloquear($id)
    {
        return $this->cambiarEstado($id, null, false, 'Tenant desbloqueado.');
    }

    public function toggleEstado(Request $request, $id)
    {
        $campo = $request->input('campo');
        $valor = $request->input('valor');
        if ($valor === 'true' || $valor === 1 || $valor === '1' || $valor === true) {
            $valor = true;
        } elseif ($valor === 'false' || $valor === 0 || $valor === '0' || $valor === false) {
            $valor = false;
        } else {
            $valor = null;
        }

        if (!in_array($campo, ['activo', 'bloqueado'], true) || $valor === null) {
            return response()->json([
                'error' => 'Debe enviar campo (activo|bloqueado) y valor (true|false).',
            ], 422);
        }

        $activo = $campo === 'activo' ? $valor : null;
        $bloqueado = $campo === 'bloqueado' ? $valor : null;
        $msg = $campo === 'activo'
            ? ($valor ? 'Tenant activado.' : 'Tenant desactivado.')
            : ($valor ? 'Tenant bloqueado.' : 'Tenant desbloqueado.');

        return $this->cambiarEstado($id, $activo, $bloqueado, $msg);
    }

    private function cambiarEstado($id, $activo, $bloqueado, $mensaje)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['error' => 'Tenant no encontrado'], 404);
            }

            $data = [];
            if ($activo !== null) {
                $data['activo'] = (bool) $activo;
            }
            if ($bloqueado !== null) {
                $data['bloqueado'] = (bool) $bloqueado;
            }
            if (empty($data)) {
                return response()->json(['error' => 'Sin cambios'], 422);
            }

            $tenant->update($data);
            $tenant->refresh();

            return response()->json([
                'message' => $mensaje,
                'tenant' => $this->enriquecer($tenant),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cambiar estado del tenant',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
