<?php

namespace App\Http\Middleware;

use Closure;
use \Firebase\JWT\JWT;
use App\AdminUser;

class AdminAuth
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token de administrador requerido'], 401);
        }

        try {
            $decoded = JWT::decode($token, env('FIRMA_TOKEN'), ['HS256']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token inválido o expirado'], 401);
        }

        if (empty($decoded->roll) || $decoded->roll !== 'admin') {
            return response()->json(['error' => 'Acceso no autorizado'], 403);
        }

        $admin = AdminUser::find($decoded->id);
        if (!$admin || !$admin->activo) {
            return response()->json(['error' => 'Administrador no válido'], 401);
        }

        $request->attributes->set('admin_user', $admin);
        $request->attributes->set('admin_jwt', $decoded);

        return $next($request);
    }
}
