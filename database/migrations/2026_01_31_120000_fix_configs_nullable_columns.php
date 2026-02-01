<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixConfigsNullableColumns extends Migration
{
    /**
     * Run the migrations.
     * Corrige columnas que no permiten NULL para evitar error al crear/actualizar configs.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'api_whatapps',
            'api_token_ws',
            'api_gmail',
            'api_token_google',
            'api_instagram',
            'token_instagram',
            'prefijo_factura',
            'google_calendar_id',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('configs', $column)) {
                DB::statement("ALTER TABLE configs MODIFY COLUMN `{$column}` VARCHAR(255) NULL DEFAULT ''");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $columns = [
            'api_whatapps',
            'api_token_ws',
            'api_gmail',
            'api_token_google',
            'api_instagram',
            'token_instagram',
            'prefijo_factura',
            'google_calendar_id',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('configs', $column)) {
                DB::statement("ALTER TABLE configs MODIFY COLUMN `{$column}` VARCHAR(255) NOT NULL DEFAULT ''");
            }
        }
    }
}
