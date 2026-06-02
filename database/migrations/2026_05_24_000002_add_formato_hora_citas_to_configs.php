<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFormatoHoraCitasToConfigs extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('configs', 'formato_hora_citas')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->string('formato_hora_citas', 10)->default('12h')->after('recordatorio_minutos');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('configs', 'formato_hora_citas')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->dropColumn('formato_hora_citas');
            });
        }
    }
}
