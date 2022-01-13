<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSuplidorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suplidors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
            $table->integer("usuario_id")->unsigned();
            $table->foreign("usuario_id")->references("id")->on("usuarios");
            $table->string('descripcion');  
            $table->string('rnc_suplidor'); 
            $table->date('fecha_registro_s');
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
        Schema::dropIfExists('suplidors');
    }
}
