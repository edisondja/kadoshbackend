<?php

use Illuminate\Database\Seeder;
use App\Paciente;

class PacienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $paciente = new Paciente();
        $paciente->nombre = "Albert";
        $paciente->apellido ="De Jesus Abreu";
        $paciente->cedula ="402-2350-30";
        $paciente->sexo= "masculino";
        $paciente->fecha_de_ingreso = date('ymd');
        $paciente->save();

        
    }
}
