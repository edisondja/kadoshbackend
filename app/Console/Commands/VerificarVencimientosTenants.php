<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tenant;
use Illuminate\Support\Facades\Log;

class VerificarVencimientosTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:verificar-vencimientos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica los vencimientos de los tenants y bloquea automáticamente los que han vencido';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Iniciando verificación de vencimientos de tenants...');

        try {
            // Obtener todos los tenants
            $tenants = Tenant::all();
            
            $vencidos = 0;
            $porVencer = 0;
            $activos = 0;

            foreach ($tenants as $tenant) {
                if ($tenant->fecha_vencimiento) {
                    if ($tenant->estaVencido()) {
                        // Si está vencido y no está bloqueado, bloquearlo automáticamente
                        if (!$tenant->bloqueado) {
                            $tenant->bloqueado = true;
                            $tenant->save();
                            
                            $this->warn("Tenant '{$tenant->nombre}' ({$tenant->subdominio}) ha vencido y ha sido bloqueado automáticamente.");
                            Log::info("Tenant '{$tenant->nombre}' ({$tenant->subdominio}) bloqueado automáticamente por vencimiento.");
                            $vencidos++;
                        } else {
                            $vencidos++;
                        }
                    } else {
                        $diasRestantes = $tenant->diasRestantes();
                        if ($diasRestantes !== null && $diasRestantes <= 7 && $diasRestantes > 0) {
                            $this->info("Tenant '{$tenant->nombre}' ({$tenant->subdominio}) vence en {$diasRestantes} días.");
                            $porVencer++;
                        } else {
                            $activos++;
                        }
                    }
                } else {
                    $activos++;
                }
            }

            $this->info("Verificación completada:");
            $this->info("- Tenants activos: {$activos}");
            $this->info("- Tenants por vencer (≤7 días): {$porVencer}");
            $this->info("- Tenants vencidos/bloqueados: {$vencidos}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error al verificar vencimientos: " . $e->getMessage());
            Log::error("Error al verificar vencimientos de tenants: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
