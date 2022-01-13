<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGastosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    
        Schema::create('gastos', function (Blueprint $table) {
            $table->increments('id');
            $table->string("rnc");
            $table->string("descripcion");
            $table->integer("suplidor_id")->unsigned();
            $table->string("tipo_de_gasto");
            $table->string("tipo_de_pago");
            $table->date('fecha_registro');
            $table->string('comprobante_fiscal');
            $table->integer("usuario_id")->unsigned();
            $table->float("total");
            $table->float("itebis");
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
        Schema::dropIfExists('gastos');
    }
}
