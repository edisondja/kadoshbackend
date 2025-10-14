<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOdontogramasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('odontogramas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('paciente_id');
            $table->integer('diente_numero');
            $table->string('superficie');
            $table->string('estado');

            // 🔸 Ajustado tipo
            $table->unsignedInteger('procedimiento_id')->nullable();

            $table->foreign('paciente_id')
                ->references('id')->on('pacientes')
                ->onDelete('cascade');

            $table->foreign('procedimiento_id')
                ->references('id')->on('procedimientos');

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
        Schema::dropIfExists('odontogramas');
    }
}
