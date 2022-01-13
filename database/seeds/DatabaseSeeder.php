<?php

use Illuminate\Database\Seeder;
use App\Paciente;
use App\Usuario;
use App\Doctor;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

            $doctor = new App\Doctor();
            $doctor->nombre= 'Alexander';
            $doctor->apellido= 'De Jesus Abreu';
            $doctor->dni= '402-23580-7';
            $doctor->numero_telefono='8095604411';
            $doctor->save();
        
            $paciente = new Paciente();
            $paciente->nombre = "Albert";
            $paciente->apellido ="De Jesus Abreu";
            $paciente->cedula ="402-2350-30";
            $paciente->sexo= "masculino";
            $paciente->fecha_de_ingreso = date('ymd');
            $paciente->save();

            $usuario = new Usuario();
            $usuario->nombre = "Edison";
            $usuario->apellido ="De jesus Abreu";
            $usuario->usuario ="edison";
            $usuario->clave="Meteoro2412";
            $usuario->roll="Administrador";
            $usuario->save();

            $sup = new Suplidor();
            $sup->nombre = 'Nitro Ultra';
            $sup->descripcion ='Suplidor de Nitro';
            $sup->rnc_suplidor = '423-5352';
            $sup->usuario_id = 1;
            $sup->fecha_registro_s =date('ymd');
            $sup->save();
             
    
    }
}
