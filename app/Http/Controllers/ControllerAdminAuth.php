<?php

namespace App\Http\Controllers;

use App\AdminUser;
use Illuminate\Http\Request;
use \Firebase\JWT\JWT;

class ControllerAdminAuth extends Controller
{
    /**
     * Login administrador (BD maestra). Retorna JWT con roll 'admin'.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'usuario' => 'required|string',
                'clave'   => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Usuario y contraseña son requeridos',
                'errors' => $e->errors(),
            ], 422);
        }

        $admin = AdminUser::on('mysql')->where('usuario', $request->usuario)->where('activo', true)->first();

        if (!$admin || !$admin->verificarClave($request->clave)) {
            return response()->json([
                'error' => 'Usuario o contraseña incorrectos',
            ], 401);
        }

        $firma = env('FIRMA_TOKEN');
        if (empty($firma)) {
            return response()->json([
                'error' => 'Configuración del servidor incompleta (FIRMA_TOKEN). Contacte al administrador.',
            ], 500);
        }

        $payload = [
            'id'      => $admin->id,
            'usuario' => $admin->usuario,
            'nombre'  => $admin->nombre,
            'apellido'=> $admin->apellido,
            'roll'    => 'admin',
            'iat'     => time(),
            'exp'     => time() + (24 * 60 * 60), // 24 horas
            'nbf'     => time(),
        ];

        try {
            $token = JWT::encode($payload, $firma, 'HS256');
        } catch (\Exception $e) {
            \Log::error('Admin login JWT encode: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar sesión. Reintente.'], 500);
        }

        return response()->json([
            'token'   => $token,
            'id'      => $admin->id,
            'nombre'  => $admin->nombre,
            'apellido'=> $admin->apellido,
            'usuario' => $admin->usuario,
        ]);
    }

    /**
     * Datos del administrador autenticado.
     */
    public function me(Request $request)
    {
        $admin = $request->attributes->get('admin_user');
        if (!$admin) {
            return response()->json(['error' => 'No autenticado'], 401);
        }
        return response()->json([
            'id'      => $admin->id,
            'usuario' => $admin->usuario,
            'nombre'  => $admin->nombre,
            'apellido'=> $admin->apellido,
        ]);
    }
}
