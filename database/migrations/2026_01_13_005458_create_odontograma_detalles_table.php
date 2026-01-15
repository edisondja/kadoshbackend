<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOdontogramaDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('odontograma_detalles', function (Blueprint $table) {
            $table->increments('id');
            // Asegúrate de que odontogramas.id sea increments y no bigIncrements
            $table->unsignedInteger('odontograma_id'); 
            $table->string('diente');
            $table->string('cara')->nullable(); // nullable por si el proc es a todo el diente
            $table->string('tipo'); // procedimiento o nota
            $table->string('descripcion')->nullable();
            $table->decimal('precio', 8, 2)->default(0); // ¡Te sugiero añadir esto para el presupuesto!
            $table->string('color')->nullable(); // Para guardar el color (Rojo, Azul, etc.)

            $table->foreign('odontograma_id')
                  ->references('id')
                  ->on('odontogramas')
                  ->onDelete('cascade');
                  
            $table->timestamps();
        }); // <-- Aquí cerraba el Schema
    } // <-- ESTA ES LA LLAVE QUE TE FALTABA

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('odontograma_detalles');
    }
}