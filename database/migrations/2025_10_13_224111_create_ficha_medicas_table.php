<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFichaMedicasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
  public function up(){
        Schema::create('ficha_medicas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('paciente_id')->unsigned();
            $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('cascade');
            $table->string('direccion')->nullable();
            $table->string('estado')->nullable();
            $table->string('ocupacion')->nullable();
            $table->string('tratamiento_actual')->nullable();
            $table->string('tratamiento_detalle')->nullable();
            $table->string('enfermedades')->nullable();
            $table->string('medicamentos')->nullable();
            $table->string('tabaquismo')->nullable();
            $table->string('alcohol')->nullable();
            $table->string('otros_habitos')->nullable();
            $table->string('antecedentes_familiares')->nullable();
            $table->string('alergias')->nullable();
            $table->string('alergias_detalle')->nullable();
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
        Schema::dropIfExists('ficha_medicas');
    }
}
