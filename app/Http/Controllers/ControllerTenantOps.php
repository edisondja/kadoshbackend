<?php

namespace App\Http\Controllers;

use App\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Operaciones de infraestructura desde el panel admin de tenants:
 * - Subir build React (ZIP) al document_root del subdominio
 * - Crear VirtualHost en Apache sites-available
 * - Sincronizar esquema SQL en uno o todos los tenants
 *
 * BD maestra: clinica (conexión mysql / Tenant model)
 */
class ControllerTenantOps extends Controller
{
    private function docrootPermitido($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = rtrim($path, '/');
        $allowed = rtrim(str_replace('\\', '/', env('TENANT_DOCROOT_PREFIX', '/var/www')), '/');
        if (strpos($path, $allowed . '/') === 0 || $path === $allowed) {
            return true;
        }
        $real = realpath($path);
        if ($real) {
            $real = rtrim(str_replace('\\', '/', $real), '/');
            return strpos($real, $allowed . '/') === 0 || $real === $allowed;
        }
        return false;
    }

    private function enriquecer(Tenant $tenant)
    {
        $tenant->dias_restantes = $tenant->diasRestantes();
        $tenant->estado = $tenant->estado;
        $tenant->esta_vencido = $tenant->estaVencido();
        $tenant->puede_acceder = $tenant->puedeAcceder();
        $tenant->dominio_resuelto = $tenant->dominioResuelto();
        $tenant->document_root_resuelto = $tenant->documentRootResuelto();
        $tenant->api_url_resuelta = $tenant->apiUrlResuelta();
        return $tenant;
    }

    /**
     * Subir ZIP del build de React al document_root del tenant.
     * El ZIP debe contener index.html y static/ en la raíz (o dentro de build/).
     */
    public function subirFrontend(Request $request, $id)
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no encontrado'], 404);
        }

        $request->validate([
            'build' => 'required|file|mimes:zip|max:204800', // 200 MB
        ]);

        $docroot = $tenant->documentRootResuelto();
        if (!$docroot) {
            return response()->json([
                'error' => 'Defina dominio o document_root en el tenant antes de subir el frontend.',
            ], 422);
        }

        if (!$this->docrootPermitido($docroot) && strpos($docroot, '/var/www/') !== 0) {
            return response()->json([
                'error' => 'document_root no permitido. Debe estar bajo /var/www/',
                'document_root' => $docroot,
            ], 422);
        }

        try {
            if (!File::isDirectory($docroot)) {
                File::makeDirectory($docroot, 0755, true);
            }

            $zipFile = $request->file('build');
            $tmpZip = storage_path('app/tmp_deploy_' . $tenant->id . '_' . time() . '.zip');
            $tmpDir = storage_path('app/tmp_deploy_extract_' . $tenant->id . '_' . time());
            File::makeDirectory($tmpDir, 0755, true);
            $zipFile->move(dirname($tmpZip), basename($tmpZip));

            $zip = new ZipArchive();
            if ($zip->open($tmpZip) !== true) {
                @unlink($tmpZip);
                File::deleteDirectory($tmpDir);
                return response()->json(['error' => 'No se pudo abrir el ZIP'], 400);
            }
            $zip->extractTo($tmpDir);
            $zip->close();
            @unlink($tmpZip);

            $source = $this->resolverRaizBuild($tmpDir);
            if (!$source) {
                File::deleteDirectory($tmpDir);
                return response()->json([
                    'error' => 'El ZIP no contiene index.html. Suba el contenido de la carpeta build/ comprimido.',
                ], 422);
            }

            // Vaciar destino (excepto logs ocultos) y copiar
            $this->vaciarDirectorio($docroot);
            $this->copiarDirectorio($source, $docroot);

            // Asegurar .htaccess SPA
            $htaccessSrc = base_path('../kadosh/public/.htaccess');
            if (!File::exists($htaccessSrc)) {
                $htaccessSrc = public_path('../resources/apache/spa.htaccess');
            }
            if (File::exists($htaccessSrc)) {
                File::copy($htaccessSrc, $docroot . '/.htaccess');
            } elseif (!File::exists($docroot . '/.htaccess')) {
                File::put($docroot . '/.htaccess', $this->htaccessSpaPorDefecto());
            }

            File::deleteDirectory($tmpDir);

            $tenant->ultimo_deploy_at = Carbon::now();
            $tenant->document_root = $docroot;
            if (empty($tenant->dominio)) {
                $tenant->dominio = $tenant->dominioResuelto();
            }
            $tenant->save();

            return response()->json([
                'message' => 'Frontend desplegado correctamente en ' . $docroot,
                'tenant' => $this->enriquecer($tenant->fresh()),
                'document_root' => $docroot,
                'archivos' => [
                    'index_html' => File::exists($docroot . '/index.html'),
                    'static' => File::isDirectory($docroot . '/static'),
                    'htaccess' => File::exists($docroot . '/.htaccess'),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('subirFrontend: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al desplegar frontend',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function resolverRaizBuild($tmpDir)
    {
        if (File::exists($tmpDir . '/index.html')) {
            return $tmpDir;
        }
        if (File::exists($tmpDir . '/build/index.html')) {
            return $tmpDir . '/build';
        }
        // Un único subdirectorio con index.html
        $dirs = File::directories($tmpDir);
        if (count($dirs) === 1 && File::exists($dirs[0] . '/index.html')) {
            return $dirs[0];
        }
        return null;
    }

    private function vaciarDirectorio($dir)
    {
        if (!File::isDirectory($dir)) {
            return;
        }
        foreach (File::directories($dir) as $d) {
            File::deleteDirectory($d);
        }
        foreach (File::files($dir) as $f) {
            File::delete($f);
        }
    }

    private function copiarDirectorio($from, $to)
    {
        File::makeDirectory($to, 0755, true, true);
        foreach (File::allFiles($from) as $file) {
            $rel = ltrim(str_replace($from, '', $file->getPathname()), '/\\');
            $dest = $to . '/' . $rel;
            File::makeDirectory(dirname($dest), 0755, true, true);
            File::copy($file->getPathname(), $dest);
        }
    }

    private function htaccessSpaPorDefecto()
    {
        return <<<'HTA'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^static/ - [L]
  RewriteCond %{REQUEST_FILENAME} -f [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^ - [L]
  RewriteRule ^ /index.html [L]
</IfModule>
HTA;
    }

    /**
     * Crear/actualizar VirtualHost en /etc/apache2/sites-available
     */
    public function provisionarVhost(Request $request, $id)
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no encontrado'], 404);
        }

        $dominio = $request->input('dominio') ?: $tenant->dominioResuelto();
        $docroot = $request->input('document_root') ?: $tenant->documentRootResuelto();

        if (!$dominio || !$docroot) {
            return response()->json([
                'error' => 'Se requiere dominio y document_root',
            ], 422);
        }

        if (strpos($docroot, '/var/www/') !== 0) {
            return response()->json([
                'error' => 'document_root debe empezar por /var/www/',
            ], 422);
        }

        $script = base_path('scripts/provision-apache-vhost.sh');
        if (!File::exists($script)) {
            return response()->json(['error' => 'Script provision-apache-vhost.sh no encontrado'], 500);
        }

        @chmod($script, 0755);

        // Preferir sudo del script restringido; si falla, intentar sin sudo (si www-data tiene permisos)
        $cmd = sprintf(
            'sudo -n %s %s %s 2>&1',
            escapeshellarg($script),
            escapeshellarg($dominio),
            escapeshellarg($docroot)
        );

        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        if ($code !== 0) {
            // Reintento escribiendo solo el .conf si el usuario del PHP puede escribir en sites-available
            $sitesAvailable = env('APACHE_SITES_AVAILABLE', '/etc/apache2/sites-available');
            $confPath = rtrim($sitesAvailable, '/') . '/' . $dominio . '.conf';
            $escritoManual = false;
            try {
                if (is_dir($sitesAvailable) && is_writable($sitesAvailable)) {
                    $conf = $this->plantillaVhost($dominio, $docroot);
                    file_put_contents($confPath, $conf);
                    if (!File::isDirectory($docroot)) {
                        File::makeDirectory($docroot, 0755, true);
                    }
                    $escritoManual = true;
                    $output[] = 'Conf escrito manualmente en ' . $confPath;
                    $output[] = 'Ejecute en el servidor: sudo a2ensite ' . $dominio . '.conf && sudo apache2ctl configtest && sudo systemctl reload apache2';
                    $code = 0;
                }
            } catch (\Exception $e) {
                $output[] = $e->getMessage();
            }

            if (!$escritoManual && $code !== 0) {
                return response()->json([
                    'error' => 'No se pudo provisionar el vhost. Configure sudoers para el script o permisos en sites-available.',
                    'detalle' => implode("\n", $output),
                    'comando_sugerido' => 'sudo ' . $script . ' ' . $dominio . ' ' . $docroot,
                ], 500);
            }
        }

        $tenant->dominio = $dominio;
        $tenant->document_root = $docroot;
        if (empty($tenant->api_url)) {
            $tenant->api_url = 'https://' . $dominio;
        }
        $tenant->vhost_enabled = true;
        $tenant->save();

        return response()->json([
            'message' => 'VirtualHost provisionado para ' . $dominio,
            'tenant' => $this->enriquecer($tenant->fresh()),
            'output' => implode("\n", $output),
            'conf' => '/etc/apache2/sites-available/' . $dominio . '.conf',
        ]);
    }

    private function plantillaVhost($dominio, $docroot)
    {
        return <<<CONF
# Generado por Kadosh / OdontoED
<VirtualHost *:80>
    ServerName {$dominio}
    ServerAlias www.{$dominio}
    DocumentRoot {$docroot}

    <Directory {$docroot}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/{$dominio}-error.log
    CustomLog \${APACHE_LOG_DIR}/{$dominio}-access.log combined
</VirtualHost>
CONF;
    }

    /**
     * Sincronizar esquema en uno, varios o todos los tenants activos.
     * Body: { "ids": [1,2] | null, "todos": true, "backup": false }
     */
    public function sincronizarEsquema(Request $request)
    {
        $todos = filter_var($request->input('todos', false), FILTER_VALIDATE_BOOLEAN);
        $ids = $request->input('ids', []);
        $backup = filter_var($request->input('backup', false), FILTER_VALIDATE_BOOLEAN);

        if (!$todos && (!is_array($ids) || count($ids) === 0)) {
            return response()->json([
                'error' => 'Envíe ids: [...] o todos: true',
            ], 422);
        }

        $query = Tenant::query();
        if ($todos) {
            $query->where('activo', 1);
        } else {
            $query->whereIn('id', $ids);
        }
        $tenants = $query->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            return response()->json(['error' => 'No hay tenants para sincronizar'], 404);
        }

        $sqlFile = database_path('sql/tenant_actualizar_esquema.sql');
        if (!File::exists($sqlFile)) {
            return response()->json(['error' => 'No existe tenant_actualizar_esquema.sql'], 500);
        }

        $extras = [
            database_path('sql/2026_07_17_tema_apariencia_configs.sql'),
            database_path('sql/2026_07_25_firma_doctor_documentos.sql'),
            database_path('sql/2026_08_28_user_sessions.sql'),
            database_path('sql/2026_08_29_asientos_contables.sql'),
            database_path('sql/2026_08_29_usuarios_bloqueado.sql'),
        ];

        $resultados = [];
        $ok = 0;
        $fail = 0;

        foreach ($tenants as $tenant) {
            $dbName = trim((string) $tenant->database_name);
            if ($dbName === '') {
                $resultados[] = [
                    'tenant_id' => $tenant->id,
                    'nombre' => $tenant->nombre,
                    'success' => false,
                    'message' => 'Sin database_name',
                ];
                $fail++;
                continue;
            }

            $item = [
                'tenant_id' => $tenant->id,
                'nombre' => $tenant->nombre,
                'database' => $dbName,
                'success' => false,
                'archivos' => [],
                'message' => '',
            ];

            try {
                if ($backup) {
                    try {
                        Artisan::call('kadosh:migrate-tenants', [
                            '--database' => $dbName,
                            '--backup' => true,
                            '--sql' => true,
                        ]);
                        $item['archivos'][] = 'artisan migrate-tenants --sql --backup';
                        $item['artisan_output'] = trim(Artisan::output());
                    } catch (\Exception $e) {
                        // Continuar con mysql directo
                        $item['archivos'][] = 'artisan omitido: ' . $e->getMessage();
                    }
                }

                $applied = $this->aplicarSqlEnBase($dbName, $sqlFile);
                $item['archivos'][] = 'tenant_actualizar_esquema.sql: ' . ($applied ? 'OK' : 'FAIL');

                foreach ($extras as $extra) {
                    if (!File::exists($extra)) {
                        continue;
                    }
                    $okExtra = $this->aplicarSqlEnBase($dbName, $extra);
                    $item['archivos'][] = basename($extra) . ': ' . ($okExtra ? 'OK' : 'FAIL');
                }

                $item['success'] = true;
                $item['message'] = 'Esquema actualizado';
                $ok++;
            } catch (\Exception $e) {
                $item['success'] = false;
                $item['message'] = $e->getMessage();
                $fail++;
            }

            $resultados[] = $item;
        }

        return response()->json([
            'message' => "Sincronización terminada: {$ok} OK, {$fail} con error",
            'ok' => $ok,
            'fail' => $fail,
            'resultados' => $resultados,
        ]);
    }

    private function aplicarSqlEnBase($database, $sqlFile)
    {
        $host = env('DB_HOST', '127.0.0.1');
        $user = env('DB_USERNAME', 'root');
        $pass = env('DB_PASSWORD', '');

        $passArg = $pass !== '' ? '-p' . escapeshellarg($pass) : '';
        $cmd = sprintf(
            'mysql -h %s -u %s %s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($user),
            $passArg,
            escapeshellarg($database),
            escapeshellarg($sqlFile)
        );

        $out = [];
        $code = 0;
        exec($cmd, $out, $code);
        if ($code !== 0) {
            \Log::warning('SQL fail on ' . $database . ': ' . implode(' ', $out));
            return false;
        }
        return true;
    }
}
