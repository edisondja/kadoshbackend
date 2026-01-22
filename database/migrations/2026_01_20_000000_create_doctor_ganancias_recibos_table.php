<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDoctorGananciasRecibosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_ganancias_recibos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_recibo');
            $table->unsignedInteger('id_doctor');
            $table->decimal('ganancia_doctor', 10, 2)->default(0); // Ganancia asignada al doctor
            $table->decimal('ganancia_clinica', 10, 2)->default(0); // Ganancia para la clínica
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->foreign('id_recibo')->references('id')->on('recibos')->onDelete('cascade');
            $table->foreign('id_doctor')->references('id')->on('doctors')->onDelete('cascade');
            $table->unique(['id_recibo', 'id_doctor']); // Un doctor solo puede tener una ganancia por recibo
            $table->index(['id_doctor', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctor_ganancias_recibos');
    }
}
