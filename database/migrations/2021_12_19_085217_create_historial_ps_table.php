<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistorialPsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historial_ps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_procedimiento')->unsigned();
            $table->integer('id_factura')->unsigned();
            $table->foreign('id_factura')->references('id')->on('facturas')->onDelete('cascade');
            $table->integer('cantidad');
            $table->integer('total');
            $table->float('estado_actual'); 
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
        Schema::dropIfExists('historial_ps');
    }
}
