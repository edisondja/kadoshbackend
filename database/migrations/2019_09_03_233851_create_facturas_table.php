<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacturasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("id_doctor")->unsigned();
            $table->foreign("id_doctor")->references("id")->on("doctors");
            $table->string('tipo_de_pago')->nullable();
            $table->integer("id_paciente")->unsigned();
            $table->foreign("id_paciente")->references("id")->on("pacientes");
            $table->float("precio_estatus");
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
        Schema::dropIfExists('facturas');
    }
}
