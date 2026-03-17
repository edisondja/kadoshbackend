<?php

use Illuminate\Database\Seeder;
use App\AdminUser;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea un usuario administrador por defecto (BD maestra).
     * Ejecutar: php artisan db:seed --class=AdminUserSeeder
     */
    public function run()
    {
        if (AdminUser::where('usuario', 'admin')->exists()) {
            $this->command->info('Ya existe un administrador con usuario "admin".');
            return;
        }

        AdminUser::create([
            'usuario'  => 'admin',
            'password' => 'Meteoro2412',
            'nombre'   => 'Administrador',
            'apellido' => 'Sistema',
            'activo'   => true,
        ]);

        $this->command->info('Administrador creado: usuario=admin, contraseña=Meteoro2412');
        $this->command->warn('Cambie la contraseña después del primer acceso.');
    }
}
