<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Tenant;

class TenantMiddleware
{
    const CACHE_DENIED_TTL_SECONDS = 60;
    const CACHE_DENIED_RETRY_AFTER = 60; // segundos (header Retry-After)

    public function handle($request, Closure $next)
    {
        // Panel / API de administración de tenants: siempre BD maestra (no aplicar lógica tenant)
        $path = $request->path();
        if ($path === 'admin-tenants'
            || strpos($path, 'api/admin') === 0
            || strpos($path, 'api/tenants') === 0
        ) {
            return $next($request);
        }

        $host = $request->getHost(); // ej: clinica1.odontoed.com o odontoed.com
        $parts = explode('.', $host);

        // Hosts de API / panel que NO son clínicas (usar BD por defecto del .env)
        $subdominiosReservados = [
            'www', 'api', 'demoserver', 'server', 'admin', 'app',
        ];
        if (count($parts) >= 3 && in_array(strtolower($parts[0]), $subdominiosReservados, true)) {
            return $next($request);
        }

        // Si no hay subdominio, continuar con la conexión por defecto
        if (count($parts) < 3) {
            return $next($request);
        }

        $subdomain = strtolower($parts[0]);
        $cacheKey = 'tenant_denied:' . $subdomain;

        // Respuesta rápida desde cache si el tenant está denegado
        $cachedDenied = Cache::get($cacheKey);
        if (is_array($cachedDenied) && !empty($cachedDenied['mensaje'])) {
            return $this->respuestaDenegada($request, $cachedDenied['mensaje'], isset($cachedDenied['tenant']) ? $cachedDenied['tenant'] : null);
        }

        try {
            $tenant = Tenant::where('subdominio', $subdomain)->first();

            if (!$tenant) {
                return $next($request);
            }

            if (!$tenant->puedeAcceder()) {
                $mensaje = 'Acceso denegado. ';

                if ($tenant->bloqueado) {
                    $mensaje .= 'El sistema está bloqueado.';
                } elseif (!$tenant->activo) {
                    $mensaje .= 'El sistema está inactivo.';
                } elseif ($tenant->estaVencido()) {
                    $mensaje .= 'La licencia ha vencido. Por favor, contacte al administrador.';
                }

                Cache::put($cacheKey, [
                    'mensaje' => $mensaje,
                    'tenant' => [
                        'id' => $tenant->id,
                        'nombre' => $tenant->nombre,
                        'subdominio' => $tenant->subdominio,
                    ],
                ], self::CACHE_DENIED_TTL_SECONDS);

                return $this->respuestaDenegada($request, $mensaje, [
                    'id' => $tenant->id,
                    'nombre' => $tenant->nombre,
                    'subdominio' => $tenant->subdominio,
                ]);
            }

            // Si estaba denegado y ahora puede, limpiar cache
            Cache::forget($cacheKey);

            $databaseName = $tenant->database_name;
        } catch (\Exception $e) {
            \Log::error('Error al verificar tenant: ' . $e->getMessage());
            return $next($request);
        }

        Config::set('database.connections.tenant.database', $databaseName);

        DB::purge('tenant');
        DB::reconnect('tenant');

        Config::set('database.default', 'tenant');

        return $next($request);
    }

    /**
     * JSON para el SPA (para que deje de reintentar); HTML para navegación directa.
     */
    private function respuestaDenegada($request, $mensaje, $tenantInfo = null)
    {
        $wantsJson = $request->ajax()
            || $request->wantsJson()
            || strpos(ltrim($request->path(), '/'), 'api/') === 0
            || stripos((string) $request->header('Accept'), 'application/json') !== false;

        if ($wantsJson) {
            return response()->json([
                'error' => $mensaje,
                'message' => $mensaje,
                'tenant_disabled' => true,
                'puede_acceder' => false,
                'valid' => false,
                'tenant' => $tenantInfo,
            ], 403)->header('Retry-After', (string) self::CACHE_DENIED_RETRY_AFTER);
        }

        return response()->view('tenant_bloqueado', [
            'mensaje' => $mensaje,
            'tenant' => $tenantInfo,
        ], 403);
    }

    /**
     * Limpia el cache de denegación (llamar al activar/desbloquear tenant).
     */
    public static function forgetDeniedCache($subdominio)
    {
        if ($subdominio) {
            Cache::forget('tenant_denied:' . strtolower($subdominio));
        }
    }
}
