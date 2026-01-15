<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFacturaFieldsToConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->string('nombre_clinica')->nullable()->after('nombre');
            $table->text('direccion_clinica')->nullable()->after('nombre_clinica');
            $table->string('telefono_clinica')->nullable()->after('direccion_clinica');
            $table->string('rnc_clinica')->nullable()->after('telefono_clinica');
            $table->string('email_clinica')->nullable()->after('rnc_clinica');
            $table->enum('tipo_numero_factura', ['comprobante', 'factura'])->default('comprobante')->after('email_clinica');
            $table->string('prefijo_factura')->nullable()->after('tipo_numero_factura'); // Ej: "NO 22" o "COMP"
            $table->boolean('usar_google_calendar')->default(false)->after('api_token_google');
            $table->string('google_calendar_id')->nullable()->after('usar_google_calendar');
            $table->integer('recordatorio_minutos')->default(30)->after('google_calendar_id'); // Minutos antes de la cita para recordar
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_clinica',
                'direccion_clinica',
                'telefono_clinica',
                'rnc_clinica',
                'email_clinica',
                'tipo_numero_factura',
                'prefijo_factura',
                'usar_google_calendar',
                'google_calendar_id',
                'recordatorio_minutos'
            ]);
        });
    }
}
