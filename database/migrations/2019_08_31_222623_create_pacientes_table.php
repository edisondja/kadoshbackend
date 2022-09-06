<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePacientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->increments('id');
            $table->string("nombre");
            $table->integer("id_doctor");
            $table->string("apellido");
            $table->string("correo_electronico");
            $table->string("telefono");
            $table->string("foto_paciente");
            $table->date("fecha_nacimiento");
            $table->datetime("fecha_de_ingreso");
            $table->string("cedula");
            $table->string('sexo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pacientes');
    }
}
