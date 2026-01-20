<?php

namespace App\Http\Controllers;

use App\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ControllerTenant extends Controller
{
    /**
     * Listar todos los tenants
     */
    public function index()
    {
        try {
            $tenants = Tenant::orderBy('created_at', 'desc')->get();
            
            // Agregar información adicional a cada tenant
            $tenants = $tenants->map(function ($tenant) {
                $tenant->dias_restantes = $tenant->diasRestantes();
                $tenant->estado = $tenant->estado;
                $tenant->esta_vencido = $tenant->estaVencido();
                $tenant->puede_acceder = $tenant->puedeAcceder();
                return $tenant;
            });
            
            return response()->json($tenants, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar tenants',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo tenant
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'subdominio' => 'required|string|max:100|unique:tenants,subdominio',
            'database_name' => 'required|string|max:255',
            'fecha_vencimiento' => 'nullable|date',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_email' => 'nullable|email|max:255',
            'contacto_telefono' => 'nullable|string|max:50',
            'notas' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tenant = Tenant::create([
                'nombre' => $request->nombre,
                'subdominio' => $request->subdominio,
                'database_name' => $request->database_name,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'activo' => $request->activo ?? true,
                'bloqueado' => $request->bloqueado ?? false,
                'contacto_nombre' => $request->contacto_nombre,
                'contacto_email' => $request->contacto_email,
                'contacto_telefono' => $request->contacto_telefono,
                'notas' => $request->notas
            ]);

            $tenant->dias_restantes = $tenant->diasRestantes();
            $tenant->estado = $tenant->estado;
            $tenant->esta_vencido = $tenant->estaVencido();
            $tenant->puede_acceder = $tenant->puedeAcceder();

            return response()->json([
                'message' => 'Tenant creado exitosamente',
                'tenant' => $tenant
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear tenant',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un tenant
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::find($id);
        
        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'subdominio' => 'sometimes|required|string|max:100|unique:tenants,subdominio,' . $id,
            'database_name' => 'sometimes|required|string|max:255',
            'fecha_vencimiento' => 'nullable|date',
            'activo' => 'sometimes|boolean',
            'bloqueado' => 'sometimes|boolean',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_email' => 'nullable|email|max:255',
            'contacto_telefono' => 'nullable|string|max:50',
            'notas' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tenant->update($request->only([
                'nombre',
                'subdominio',
                'database_name',
                'fecha_vencimiento',
                'activo',
                'bloqueado',
                'contacto_nombre',
                'contacto_email',
                'contacto_telefono',
                'notas'
            ]));

            $tenant->refresh();
            $tenant->dias_restantes = $tenant->diasRestantes();
            $tenant->estado = $tenant->estado;
            $tenant->esta_vencido = $tenant->estaVencido();
            $tenant->puede_acceder = $tenant->puedeAcceder();

            return response()->json([
                'message' => 'Tenant actualizado exitosamente',
                'tenant' => $tenant
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar tenant',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un tenant
     */
    public function destroy($id)
    {
        $tenant = Tenant::find($id);
        
        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant no encontrado'
            ], 404);
        }

        try {
            $tenant->delete();
            
            return response()->json([
                'message' => 'Tenant eliminado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar tenant',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un tenant específico
     */
    public function show($id)
    {
        try {
            $tenant = Tenant::find($id);
            
            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant no encontrado'
                ], 404);
            }

            $tenant->dias_restantes = $tenant->diasRestantes();
            $tenant->estado = $tenant->estado;
            $tenant->esta_vencido = $tenant->estaVencido();
            $tenant->puede_acceder = $tenant->puedeAcceder();

            return response()->json($tenant, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar tenant',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar estado de un tenant por subdominio
     */
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
}
