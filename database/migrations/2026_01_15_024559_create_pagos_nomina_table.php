<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePagosNominaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagos_nomina', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('doctor_id')->nullable(); // Para doctores
            $table->unsignedInteger('empleado_id')->nullable(); // Para empleados
            $table->date('fecha_pago');
            $table->date('periodo_inicio'); // Inicio del período de pago
            $table->date('periodo_fin'); // Fin del período de pago
            $table->decimal('monto_comisiones', 10, 2)->default(0); // Comisiones por procedimientos
            $table->decimal('salario_base', 10, 2)->default(0); // Salario fijo (si aplica)
            $table->decimal('total_pago', 10, 2); // Total a pagar
            $table->string('estado')->default('pendiente'); // pendiente, pagado, cancelado
            $table->text('comentarios')->nullable();
            $table->string('tipo')->default('comision'); // comision, salario, mixto
            $table->timestamps();
            
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');
            $table->index(['doctor_id', 'periodo_inicio', 'periodo_fin']);
            $table->index(['empleado_id', 'periodo_inicio', 'periodo_fin']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagos_nomina');
    }
}
