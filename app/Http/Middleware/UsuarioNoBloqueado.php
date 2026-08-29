<?php

namespace App\Http\Middleware;

use Closure;
use \Firebase\JWT\JWT;
use App\Usuario;
use App\Http\Controllers\ControllerUsuario;

class UsuarioNoBloqueado
{
    public function handle($request, Closure $next)
    {
        $path = $request->path();

        if (strpos($path, 'api/login/') === 0) {
            return $next($request);
        }

        $token = $request->bearerToken();
        if (!$token) {
            return $next($request);
        }

        try {
            $decoded = JWT::decode($token, env('FIRMA_TOKEN'), ['HS256']);
            $userId = (int) ($decoded->id ?? 0);
            if ($userId <= 0) {
                return $next($request);
            }

            $usuario = Usuario::find($userId);
            $controllerUsuario = app(ControllerUsuario::class);
            if ($usuario && $controllerUsuario->usuarioEstaBloqueado($usuario)) {
                return response()->json([
                    'success' => false,
                    'blocked' => true,
                    'valid' => false,
                    'message' => 'Su cuenta fue bloqueada por un administrador. No puede usar el sistema.',
                ], 403);
            }
        } catch (\Exception $e) {
            // Token inválido: dejar que cada endpoint responda como corresponda.
        }

        return $next($request);
    }
}
