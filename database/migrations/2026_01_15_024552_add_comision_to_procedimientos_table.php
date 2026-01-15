<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddComisionToProcedimientosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('procedimientos', function (Blueprint $table) {
            $table->decimal('comision', 5, 2)->default(0)->after('precio'); // Porcentaje de comisión (0-100)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('procedimientos', function (Blueprint $table) {
            $table->dropColumn('comision');
        });
    }
}
