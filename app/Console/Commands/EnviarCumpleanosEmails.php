<?php

namespace App\Console\Commands;

use App\Config;
use App\Services\CumpleanosEmailService;
use App\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnviarCumpleanosEmails extends Command
{
    protected $signature = 'cumpleanos:enviar
                            {--database= : Solo esta base de datos tenant}
                            {--dry-run : Simular sin enviar correos}
                            {--test-to= : Enviar un correo de prueba a esta dirección}';

    protected $description = 'Envía correos automáticos a pacientes que cumplen años hoy (todos los tenants activos)';

    /** @var CumpleanosEmailService */
    protected $service;

    public function __construct(CumpleanosEmailService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $testTo = trim((string) $this->option('test-to'));

        if ($testTo !== '') {
            return $this->enviarPrueba($testTo, $dryRun);
        }

        $this->info('Cumpleaños — ' . Carbon::now()->format('Y-m-d H:i') . ($dryRun ? ' (simulación)' : ''));

        $tenants = $this->resolverTenants();
        if (empty($tenants)) {
            $this->warn('No hay tenants/base de datos para procesar.');
            return 1;
        }

        $defaultDb = config('database.connections.mysql.database');
        $totalEnviados = 0;
        $totalOmitidos = 0;
        $totalErrores = 0;

        foreach ($tenants as $tenant) {
            $dbName = is_object($tenant) ? $tenant->database_name : $tenant['database_name'];
            $label = is_object($tenant) ? ($tenant->nombre . ' [' . $tenant->subdominio . ']') : $dbName;

            if (is_object($tenant) && !$tenant->puedeAcceder()) {
                $this->line("  ⊘ {$label} — tenant inactivo/bloqueado/vencido, omitido");
                continue;
            }

            if (!$this->conectarBaseDatos($dbName)) {
                $this->error("  ✗ {$label} — no se pudo conectar a {$dbName}");
                $totalErrores++;
                continue;
            }

            $this->line('');
            $this->info("━━━ {$label} ({$dbName}) ━━━");

            try {
                $resultado = $this->procesarTenant($dbName, $dryRun);
                $totalEnviados += $resultado['enviados'];
                $totalOmitidos += $resultado['omitidos'];
                $totalErrores += $resultado['errores'];
            } catch (\Exception $e) {
                $this->error('  Error: ' . $e->getMessage());
                Log::error('cumpleanos:enviar tenant ' . $dbName . ': ' . $e->getMessage());
                $totalErrores++;
            }
        }

        if ($defaultDb) {
            $this->conectarBaseDatos($defaultDb);
        }

        $this->line('');
        $this->info("Resumen: {$totalEnviados} enviados, {$totalOmitidos} omitidos, {$totalErrores} errores");

        return $totalErrores > 0 ? 1 : 0;
    }

    protected function enviarPrueba($correo, $dryRun)
    {
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->error('Correo de prueba no válido: ' . $correo);
            return 1;
        }

        $db = $this->option('database') ?: env('DB_DATABASE');
        if ($db && !$this->conectarBaseDatos($db)) {
            $this->error('No se pudo conectar a la BD: ' . $db);
            return 1;
        }

        $config = Config::first();
        $paciente = new \App\Paciente();
        $paciente->id = 0;
        $paciente->nombre = 'Edison';
        $paciente->apellido = 'De Jesus';
        $paciente->correo_electronico = $correo;

        if ($dryRun) {
            $mensaje = $this->service->construirMensaje(
                $config ?: new Config(),
                $paciente,
                $this->service->nombreClinicaDesdeConfig($config)
            );
            $this->info('[dry-run] Correo de prueba → ' . $correo);
            $this->line($mensaje);
            return 0;
        }

        $ok = $this->service->enviarCorreoPaciente($paciente, $config, false);
        if ($ok) {
            $this->info('Correo de prueba enviado a ' . $correo);
            return 0;
        }

        $this->error('No se pudo enviar el correo de prueba. Revise MAIL_* en .env y storage/logs/laravel.log');
        return 1;
    }

    protected function procesarTenant($dbName, $dryRun)
    {
        $enviados = 0;
        $omitidos = 0;
        $errores = 0;

        if (! $this->tablaExiste('pacientes')) {
            $this->warn('  Tabla pacientes no existe, omitido.');
            return compact('enviados', 'omitidos', 'errores');
        }

        $config = Config::first();
        $pacientes = $this->service->pacientesCumpleanerosHoy();

        if ($pacientes->isEmpty()) {
            $this->comment('  Sin cumpleaños hoy con correo válido.');
            return compact('enviados', 'omitidos', 'errores');
        }

        foreach ($pacientes as $paciente) {
            if ($this->service->yaEnviadoHoy($dbName, $paciente->id)) {
                $this->line("  · {$paciente->nombre} {$paciente->apellido} — ya enviado hoy");
                $omitidos++;
                continue;
            }

            $correo = trim((string) $paciente->correo_electronico);
            if ($dryRun) {
                $this->line("  → [dry-run] {$paciente->nombre} → {$correo}");
                $enviados++;
                continue;
            }

            $ok = $this->service->enviarCorreoPaciente($paciente, $config, false);
            if ($ok) {
                $this->service->marcarEnviadoHoy($dbName, $paciente->id);
                $this->info("  ✓ {$paciente->nombre} {$paciente->apellido} → {$correo}");
                $enviados++;
            } else {
                $this->warn("  ✗ Falló envío a {$paciente->nombre} ({$correo})");
                $errores++;
            }
        }

        return compact('enviados', 'omitidos', 'errores');
    }

    protected function resolverTenants()
    {
        if ($only = $this->option('database')) {
            return [(object) [
                'nombre' => $only,
                'subdominio' => $only,
                'database_name' => trim($only),
                'activo' => true,
                'bloqueado' => false,
                'fecha_vencimiento' => null,
            ]];
        }

        try {
            $tenants = Tenant::where('activo', 1)->orderBy('id')->get();
            if ($tenants->count() > 0) {
                return $tenants;
            }
        } catch (\Exception $e) {
            $this->warn('Tabla tenants no disponible: ' . $e->getMessage());
        }

        $db = env('DB_DATABASE');
        if (!$db) {
            return [];
        }

        return [(object) [
            'nombre' => $db,
            'subdominio' => $db,
            'database_name' => trim($db),
            'activo' => true,
            'bloqueado' => false,
            'fecha_vencimiento' => null,
        ]];
    }

    protected function conectarBaseDatos($dbName)
    {
        config(['database.connections.mysql.database' => $dbName]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        try {
            DB::connection('mysql')->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function tablaExiste($table)
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }
}
