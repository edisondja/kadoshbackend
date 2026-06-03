<?php

namespace App\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateTenants extends Command
{
    protected $signature = 'kadosh:migrate-tenants
                            {--backup : Crear backup .sql antes de migrar (requiere mysqldump)}
                            {--sql : Aplicar archivos nuevos en database/sql/ (idempotente)}
                            {--database= : Solo esta base de datos}
                            {--skip-tenants-table : Usar solo DB_DATABASE del .env}';

    protected $description = 'Aplica migraciones Laravel (y SQL opcional) a cada base de datos tenant';

    protected $envBackupPath;

    public function handle()
    {
        $this->envBackupPath = base_path('.env.backup_migrate_' . date('Ymd_His'));

        $databases = $this->resolveDatabases();
        if (empty($databases)) {
            $this->error('No hay bases de datos que procesar.');
            return 1;
        }

        $this->info('Bases de datos: ' . implode(', ', $databases));
        $ok = 0;
        $fail = 0;

        foreach ($databases as $dbName) {
            $this->line('');
            $this->info("━━━ {$dbName} ━━━");

            if (!$this->switchDatabase($dbName)) {
                $fail++;
                continue;
            }

            if ($this->option('backup') && !$this->backupDatabase($dbName)) {
                $this->warn("  Backup omitido o falló; continuando...");
            }

            if (!$this->runMigrations($dbName)) {
                $fail++;
                continue;
            }

            if ($this->option('sql') && !$this->runPendingSqlFiles($dbName)) {
                $fail++;
                continue;
            }

            $ok++;
            $this->info("  ✓ {$dbName} listo");
        }

        $this->restoreEnv();

        $this->line('');
        $this->info("Completado: {$ok} OK, {$fail} con error");

        return $fail > 0 ? 1 : 0;
    }

    protected function resolveDatabases()
    {
        if ($only = $this->option('database')) {
            return [trim($only)];
        }

        if ($this->option('skip-tenants-table')) {
            $db = env('DB_DATABASE');
            return $db ? [trim($db)] : [];
        }

        try {
            $tenants = Tenant::where('activo', 1)->orderBy('id')->get();
            if ($tenants->count() > 0) {
                $names = [];
                foreach ($tenants as $t) {
                    if (!empty($t->database_name)) {
                        $names[] = trim($t->database_name);
                    }
                }
                if (!empty($names)) {
                    return array_values(array_unique($names));
                }
            }
        } catch (\Exception $e) {
            $this->warn('Tabla tenants no disponible: ' . $e->getMessage());
        }

        $db = env('DB_DATABASE');
        return $db ? [trim($db)] : [];
    }

    protected function switchDatabase($dbName)
    {
        if (!file_exists(base_path('.env'))) {
            $this->error('  No existe .env');
            return false;
        }

        if (!file_exists($this->envBackupPath)) {
            copy(base_path('.env'), $this->envBackupPath);
        }

        $env = file_get_contents(base_path('.env'));
        if (preg_match('/^DB_DATABASE=/m', $env)) {
            $env = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=' . $dbName, $env);
        } else {
            $env .= "\nDB_DATABASE={$dbName}\n";
        }
        file_put_contents(base_path('.env'), $env);

        config(['database.connections.mysql.database' => $dbName]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        try {
            DB::connection('mysql')->getPdo();
            return true;
        } catch (\Exception $e) {
            $this->error('  No se pudo conectar: ' . $e->getMessage());
            return false;
        }
    }

    protected function restoreEnv()
    {
        if ($this->envBackupPath && file_exists($this->envBackupPath)) {
            copy($this->envBackupPath, base_path('.env'));
            @unlink($this->envBackupPath);
            $this->comment('.env restaurado');
        }
    }

    protected function backupDatabase($dbName)
    {
        $dir = storage_path('backups');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . '/backup_' . $dbName . '_' . date('Ymd_His') . '.sql';
        $host = env('DB_HOST', '127.0.0.1');
        $user = env('DB_USERNAME', 'root');
        $pass = env('DB_PASSWORD', '');

        $cmd = sprintf(
            'mysqldump -h %s -u %s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($user),
            $pass !== '' ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($dbName),
            escapeshellarg($file)
        );

        exec($cmd, $out, $code);
        if ($code === 0) {
            $this->comment("  Backup: {$file}");
            return true;
        }

        $this->warn('  Backup falló: ' . implode("\n", $out));
        return false;
    }

    protected function runMigrations($dbName)
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
            if ($output !== '') {
                $this->line('  ' . str_replace("\n", "\n  ", $output));
            }
            return true;
        } catch (\Exception $e) {
            $this->error('  migrate: ' . $e->getMessage());
            return false;
        }
    }

    protected function runPendingSqlFiles($dbName)
    {
        $sqlDir = database_path('sql');
        if (!is_dir($sqlDir)) {
            return true;
        }

        $files = glob($sqlDir . '/*.sql');
        sort($files);

        if (!Schema::hasTable('schema_sql_applied')) {
            $this->warn('  Ejecute migrate primero (tabla schema_sql_applied)');
            return true;
        }

        $host = env('DB_HOST', '127.0.0.1');
        $user = env('DB_USERNAME', 'root');
        $pass = env('DB_PASSWORD', '');

        foreach ($files as $path) {
            $filename = basename($path);
            if (in_array($filename, ['local_exportar_importar.sql'], true)) {
                continue;
            }

            $exists = DB::table('schema_sql_applied')->where('filename', $filename)->exists();
            if ($exists) {
                continue;
            }

            $sql = file_get_contents($path);
            $sql = preg_replace('/^\s*USE\s+[^;]+;\s*$/mi', '', $sql);
            if (trim($sql) === '') {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'kadosh_sql_');
            file_put_contents($tmp, $sql);

            $passArg = $pass !== '' ? '-p' . escapeshellarg($pass) : '';
            $cmd = sprintf(
                'mysql -h %s -u %s %s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($user),
                $passArg,
                escapeshellarg($dbName),
                escapeshellarg($tmp)
            );

            exec($cmd, $out, $code);
            @unlink($tmp);

            if ($code !== 0) {
                $this->error("  SQL {$filename}: " . implode(' ', $out));
                return false;
            }

            DB::table('schema_sql_applied')->insert([
                'filename' => $filename,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);
            $this->comment("  SQL aplicado: {$filename}");
        }

        return true;
    }
}
