<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Tenant;
use Illuminate\Http\Response;

class TenantMiddleware
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost(); // ej: clinica1.odontoed.com o odontoed.com
        $parts = explode('.', $host);

        //Si no hay subdominio, continuar con la conexión por defecto
        if (count($parts) < 3 || $parts[0] === 'www') {
            return $next($request);
        }

        // Extraer el subdominio (primer segmento)
        $subdomain = $parts[0];

        // Verificar estado del tenant antes de permitir acceso
        try {
            // Consultar la tabla de tenants (siempre en la base de datos maestra)
            // El modelo Tenant tiene $connection = 'mysql' definido
            $tenant = Tenant::where('subdominio', $subdomain)->first();
            
            if (!$tenant) {
                // Si no existe el tenant, continuar con conexión por defecto
                return $next($request);
            }
            
            // Verificar si el tenant puede acceder
            if (!$tenant->puedeAcceder()) {
                $mensaje = 'Acceso denegado. ';
                
                if ($tenant->bloqueado) {
                    $mensaje .= 'El sistema está bloqueado.';
                } elseif (!$tenant->activo) {
                    $mensaje .= 'El sistema está inactivo.';
                } elseif ($tenant->estaVencido()) {
                    $mensaje .= 'La licencia ha vencido. Por favor, contacte al administrador.';
                }
                
                return response()->view('tenant_bloqueado', [
                    'mensaje' => $mensaje,
                    'tenant' => $tenant
                ], 403);
            }
            
            // Usar el database_name del tenant (configurado en la tabla tenants)
            $databaseName = $tenant->database_name;
            
        } catch (\Exception $e) {
            // Si hay error al verificar, loguear y continuar con conexión por defecto
            \Log::error('Error al verificar tenant: ' . $e->getMessage());
            return $next($request);
        }

        // Configurar conexión tenant con el database_name del registro
        Config::set('database.connections.tenant.database', $databaseName);

        DB::purge('tenant');
        DB::reconnect('tenant');

        // Opcional: usar tenant como conexión por defecto
        Config::set('database.default', 'tenant');

        return $next($request);
    }
}
