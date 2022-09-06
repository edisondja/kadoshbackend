<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePresupuestosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->increments('id');
            $table->string("nombre");
            $table->text("factura");
            $table->integer("paciente_id")->unsigned();
            $table->foreign("paciente_id")->references("id")->on("pacientes")->onDelete('cascade');
            $table->integer("doctor_id")->unsigned();
            $table->foreign("doctor_id")->references("id")->on("doctors");
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
        Schema::dropIfExists('presupuestos');
    }
}
