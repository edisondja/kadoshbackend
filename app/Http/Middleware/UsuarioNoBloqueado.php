<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
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
            // firebase/php-jwt v5 (producción): decode($jwt, $key, array $allowed_algs)
            // v6+ usa new Key($key, $alg) — no disponible aquí.
            $decoded = JWT::decode($token, env('FIRMA_TOKEN'), array('HS256'));
            $userId = isset($decoded->id) ? (int) $decoded->id : 0;
            if ($userId <= 0) {
                return $next($request);
            }

            $usuario = Usuario::find($userId);
            $controllerUsuario = app(ControllerUsuario::class);
            if ($usuario && $controllerUsuario->usuarioEstaBloqueado($usuario)) {
                return response()->json(array(
                    'success' => false,
                    'blocked' => true,
                    'valid' => false,
                    'message' => 'Su cuenta fue bloqueada por un administrador. No puede usar el sistema.',
                ), 403);
            }
        } catch (\Throwable $e) {
            // Token inválido o lib JWT antigua: no bloquear la petición.
        } catch (\Exception $e) {
            // PHP < 7 fallback
        }

        return $next($request);
    }
}
