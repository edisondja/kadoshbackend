<?php

use Illuminate\Database\Seeder;
use App\Usuario;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
         $usuario = new Usuario();
         $usuario->nombre = "Edison";
         $usuario->apellido ="De jesus Abreu";
         $usuario->usuario ="edison";
         $usuario->clave="Meteoro2412";
         $usuario->roll="Administrador";
         $usuario->save();

    }
}
