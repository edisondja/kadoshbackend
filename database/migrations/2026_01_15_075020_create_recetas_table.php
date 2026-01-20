<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_paciente');
            $table->foreign('id_paciente')->references('id')->on('pacientes')->onDelete('cascade');
            $table->unsignedInteger('id_doctor');
            $table->foreign('id_doctor')->references('id')->on('doctors')->onDelete('cascade');
            $table->text('medicamentos')->nullable(); // JSON con lista de medicamentos (NULL cuando se usa texto libre)
            $table->text('indicaciones')->nullable();
            $table->text('diagnostico')->nullable();
            $table->date('fecha');
            $table->string('codigo_receta')->unique();
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
        Schema::dropIfExists('recetas');
    }
}
