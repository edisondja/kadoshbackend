<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Firebase\JWT\JWT;
use Carbon\Carbon;
use DB;
use App;

class ControllerSesiones extends Controller
{
    private function tablaExiste()
    {
        try {
            return DB::getSchemaBuilder()->hasTable('user_sessions');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function parseUserAgent($ua)
    {
        $ua = strtolower((string) $ua);
        $dispositivo = 'Escritorio';
        if (strpos($ua, 'ipad') !== false || (strpos($ua, 'tablet') !== false && strpos($ua, 'mobile') === false)) {
            $dispositivo = 'Tablet';
        } elseif (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
            $dispositivo = 'Móvil';
        }

        $navegador = 'Desconocido';
        if (strpos($ua, 'edg/') !== false || strpos($ua, 'edge') !== false) {
            $navegador = 'Microsoft Edge';
        } elseif (strpos($ua, 'chrome') !== false) {
            $navegador = 'Google Chrome';
        } elseif (strpos($ua, 'firefox') !== false) {
            $navegador = 'Mozilla Firefox';
        } elseif (strpos($ua, 'safari') !== false) {
            $navegador = 'Safari';
        } elseif (strpos($ua, 'opera') !== false || strpos($ua, 'opr/') !== false) {
            $navegador = 'Opera';
        }

        return [
            'dispositivo' => $dispositivo,
            'navegador' => $navegador,
        ];
    }

    private function resolverIpCliente(Request $request)
    {
        $xff = $request->header('X-Forwarded-For');
        if ($xff) {
            $parts = explode(',', $xff);
            $ip = trim($parts[0]);
            if ($ip !== '') {
                return $ip;
            }
        }
        return $request->ip();
    }

    private function ubicacionAproximadaPorIp($ip)
    {
        if (!$ip) {
            return 'Ubicación no disponible';
        }
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
            return 'Red local';
        }
        try {
            $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,regionName,city,query';
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true,
                ],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if (!$json) {
                return 'Ubicación no disponible';
            }
            $data = @json_decode($json, true);
            if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
                return 'Ubicación no disponible';
            }
            $partes = array_values(array_filter([
                trim((string) ($data['country'] ?? '')),
                trim((string) ($data['regionName'] ?? '')),
                trim((string) ($data['city'] ?? '')),
            ]));
            return count($partes) > 0 ? implode(' / ', $partes) : 'Ubicación no disponible';
        } catch (\Exception $e) {
            return 'Ubicación no disponible';
        }
    }

    private function autenticarDesdeRequest(Request $request)
    {
        $usuarioId = $request->header('usuario_id') ?? $request->input('usuario_id');
        $token = $request->bearerToken();

        if (!$usuarioId || !$token) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no válida. Inicie sesión nuevamente.',
            ], 401);
        }

        try {
            $decoded = JWT::decode($token, env('FIRMA_TOKEN'), ['HS256']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión inválida o expirada.',
            ], 401);
        }

        if ((int) ($decoded->id ?? 0) !== (int) $usuarioId) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no válida para este usuario.',
            ], 403);
        }

        $usuario = App\Usuario::find($usuarioId);
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 403);
        }

        return [
            'usuario' => $usuario,
            'decoded' => $decoded,
            'jti' => isset($decoded->jti) ? (string) $decoded->jti : null,
        ];
    }

    private function sesionSigueActiva($jti)
    {
        if (!$jti || !$this->tablaExiste()) {
            return true;
        }

        $session = DB::table('user_sessions')->where('jti', $jti)->first();
        if (!$session) {
            return false;
        }
        if (!empty($session->revoked_at)) {
            return false;
        }
        if (Carbon::parse($session->expires_at)->lt(Carbon::now())) {
            return false;
        }

        return true;
    }

    public function registrarSesionLogin($usuario, Request $request, $jti, $expiresAt)
    {
        if (!$this->tablaExiste()) {
            return;
        }

        $ip = $this->resolverIpCliente($request);
        $ubicacion = $this->ubicacionAproximadaPorIp($ip);
        $ua = (string) $request->header('User-Agent', 'No disponible');
        $parsed = $this->parseUserAgent($ua);
        $now = Carbon::now();

        DB::table('user_sessions')->insert([
            'usuario_id' => (int) $usuario->id,
            'jti' => $jti,
            'ip_address' => $ip,
            'ubicacion' => $ubicacion,
            'user_agent' => substr($ua, 0, 1000),
            'dispositivo' => $parsed['dispositivo'],
            'navegador' => $parsed['navegador'],
            'last_active_at' => $now,
            'expires_at' => $expiresAt,
            'created_at' => $now,
        ]);
    }

    public function verificar(Request $request)
    {
        $auth = $this->autenticarDesdeRequest($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) {
            return $auth;
        }

        $jti = $auth['jti'];
        if (!$jti) {
            return response()->json(['valid' => true, 'legacy' => true]);
        }

        if (!$this->sesionSigueActiva($jti)) {
            return response()->json([
                'valid' => false,
                'message' => 'Su sesión fue cerrada o expiró.',
            ], 401);
        }

        if ($this->tablaExiste()) {
            DB::table('user_sessions')
                ->where('jti', $jti)
                ->update(['last_active_at' => Carbon::now()]);
        }

        return response()->json(['valid' => true, 'jti' => $jti]);
    }

    public function listar(Request $request)
    {
        $auth = $this->autenticarDesdeRequest($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) {
            return $auth;
        }

        $controllerUsuario = app(ControllerUsuario::class);
        if (!$controllerUsuario->usuarioTienePermiso($auth['usuario'], 'sesiones_activas')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para ver sesiones activas.',
            ], 403);
        }

        if (!$this->tablaExiste()) {
            return response()->json([
                'success' => true,
                'sesiones' => [],
                'mensaje' => 'La tabla de sesiones aún no está configurada en este tenant.',
            ]);
        }

        $ahora = Carbon::now();
        $sesiones = DB::table('user_sessions as s')
            ->join('usuarios as u', 'u.id', '=', 's.usuario_id')
            ->whereNull('s.revoked_at')
            ->where('s.expires_at', '>', $ahora)
            ->select(
                's.id',
                's.jti',
                's.usuario_id',
                's.ip_address',
                's.ubicacion',
                's.dispositivo',
                's.navegador',
                's.user_agent',
                's.last_active_at',
                's.expires_at',
                's.created_at',
                'u.nombre',
                'u.apellido',
                'u.usuario as login',
                'u.roll'
            )
            ->orderBy('s.last_active_at', 'desc')
            ->get();

        $jtiActual = $auth['jti'];
        $data = $sesiones->map(function ($s) use ($jtiActual) {
            return [
                'id' => $s->id,
                'jti' => $s->jti,
                'usuario_id' => $s->usuario_id,
                'nombre' => trim(($s->nombre ?? '') . ' ' . ($s->apellido ?? '')),
                'login' => $s->login,
                'roll' => $s->roll,
                'ip_address' => $s->ip_address,
                'ubicacion' => $s->ubicacion,
                'dispositivo' => $s->dispositivo,
                'navegador' => $s->navegador,
                'user_agent' => $s->user_agent,
                'last_active_at' => $s->last_active_at,
                'expires_at' => $s->expires_at,
                'created_at' => $s->created_at,
                'es_actual' => $jtiActual && $s->jti === $jtiActual,
            ];
        });

        return response()->json([
            'success' => true,
            'sesiones' => $data,
            'total' => $data->count(),
        ]);
    }

    public function revocar(Request $request, $jti)
    {
        $auth = $this->autenticarDesdeRequest($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) {
            return $auth;
        }

        if (!$this->tablaExiste()) {
            return response()->json(['success' => false, 'message' => 'Sesiones no configuradas.'], 400);
        }

        $session = DB::table('user_sessions')->where('jti', $jti)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Sesión no encontrada.'], 404);
        }

        $controllerUsuario = app(ControllerUsuario::class);
        $esAdmin = $controllerUsuario->usuarioTienePermiso($auth['usuario'], 'sesiones_activas');
        $esPropia = (int) $session->usuario_id === (int) $auth['usuario']->id;

        if (!$esAdmin && !$esPropia) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para cerrar esta sesión.',
            ], 403);
        }

        if (!empty($session->revoked_at)) {
            return response()->json(['success' => true, 'message' => 'La sesión ya estaba cerrada.']);
        }

        DB::table('user_sessions')
            ->where('jti', $jti)
            ->update(['revoked_at' => Carbon::now()]);

        return response()->json(['success' => true, 'message' => 'Sesión cerrada correctamente.']);
    }

    public function revocarUsuario(Request $request, $usuarioId)
    {
        $auth = $this->autenticarDesdeRequest($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) {
            return $auth;
        }

        $controllerUsuario = app(ControllerUsuario::class);
        if (!$controllerUsuario->usuarioTienePermiso($auth['usuario'], 'sesiones_activas')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para cerrar sesiones de otros usuarios.',
            ], 403);
        }

        if (!$this->tablaExiste()) {
            return response()->json(['success' => false, 'message' => 'Sesiones no configuradas.'], 400);
        }

        $jtiActual = $request->input('except_jti', $auth['jti']);
        $query = DB::table('user_sessions')
            ->where('usuario_id', (int) $usuarioId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', Carbon::now());

        if ($jtiActual) {
            $query->where('jti', '!=', $jtiActual);
        }

        $cerradas = $query->update(['revoked_at' => Carbon::now()]);

        return response()->json([
            'success' => true,
            'message' => 'Sesiones del usuario cerradas.',
            'cerradas' => $cerradas,
        ]);
    }

    public function revocarTodas(Request $request)
    {
        $auth = $this->autenticarDesdeRequest($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) {
            return $auth;
        }

        $controllerUsuario = app(ControllerUsuario::class);
        if (!$controllerUsuario->usuarioTienePermiso($auth['usuario'], 'sesiones_activas')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para cerrar sesiones de otros usuarios.',
            ], 403);
        }

        if (!$this->tablaExiste()) {
            return response()->json(['success' => false, 'message' => 'Sesiones no configuradas.'], 400);
        }

        $jtiActual = $auth['jti'];
        $query = DB::table('user_sessions')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', Carbon::now());

        if ($jtiActual) {
            $query->where('jti', '!=', $jtiActual);
        }

        $cerradas = $query->update(['revoked_at' => Carbon::now()]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las demás sesiones fueron cerradas.',
            'cerradas' => $cerradas,
        ]);
    }

    public function logout(Request $request)
    {
        $auth = $this->autenticarDesdeRequest($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) {
            return $auth;
        }

        if (!$this->tablaExiste() || !$auth['jti']) {
            return response()->json(['success' => true]);
        }

        DB::table('user_sessions')
            ->where('jti', $auth['jti'])
            ->update(['revoked_at' => Carbon::now()]);

        return response()->json(['success' => true, 'message' => 'Sesión cerrada.']);
    }
}
