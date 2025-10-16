<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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

        // Configurar conexión tenant
        $databaseName = 'tenant_' . $subdomain;
        $username = 'tenant_' . $subdomain;  // o un usuario fijo si prefieres
        #$password = 'tu_password_default';

        Config::set('database.connections.tenant.database', $databaseName);
        #Config::set('database.connections.tenant.username', 'root');
        #Config::set('database.connections.tenant.password', $password);

        DB::purge('tenant');
        DB::reconnect('tenant');

        // Opcional: usar tenant como conexión por defecto
        Config::set('database.default', 'tenant');

        return $next($request);
    }
}
