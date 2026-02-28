<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddMensajeCumpleanosToConfigs extends Migration
{
    /**
     * Run the migrations.
     * Añade la columna mensaje_cumpleanos para el mensaje de cumpleaños configurable.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('configs', 'mensaje_cumpleanos')) {
            DB::statement('ALTER TABLE configs ADD COLUMN mensaje_cumpleanos TEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('configs', 'mensaje_cumpleanos')) {
            DB::statement('ALTER TABLE configs DROP COLUMN mensaje_cumpleanos');
        }
    }
}
