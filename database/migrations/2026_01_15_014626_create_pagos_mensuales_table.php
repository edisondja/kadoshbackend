<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePagosMensualesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagos_mensuales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id');
            $table->date('fecha_pago');
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 10, 2);
            $table->string('estado')->default('pendiente'); // pendiente, pagado, vencido
            $table->text('comentarios')->nullable();
            $table->integer('dias_gracia')->default(3); // Días de gracia antes del corte
            $table->timestamps();
            
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->index('fecha_vencimiento');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagos_mensuales');
    }
}
