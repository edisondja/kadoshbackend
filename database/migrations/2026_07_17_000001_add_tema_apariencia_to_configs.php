<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTemaAparienciaToConfigs extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('configs', 'tema_apariencia')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->string('tema_apariencia', 20)->default('classic')->after('formato_hora_citas');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('configs', 'tema_apariencia')) {
            Schema::table('configs', function (Blueprint $table) {
                $table->dropColumn('tema_apariencia');
            });
        }
    }
}
