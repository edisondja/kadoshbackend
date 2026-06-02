<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPorcentajeIngresosToDoctors extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('doctors', 'porcentaje_ingresos')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->decimal('porcentaje_ingresos', 5, 2)->default(0)->after('estado');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('doctors', 'porcentaje_ingresos')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('porcentaje_ingresos');
            });
        }
    }
}
