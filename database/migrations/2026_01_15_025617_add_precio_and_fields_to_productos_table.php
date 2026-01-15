<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrecioAndFieldsToProductosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio', 10, 2)->default(0)->after('descripcion');
            $table->string('codigo')->nullable()->after('nombre');
            $table->string('categoria')->nullable()->after('precio');
            $table->integer('stock_minimo')->default(0)->after('cantidad');
            $table->boolean('activo')->default(true)->after('stock_minimo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['precio', 'codigo', 'categoria', 'stock_minimo', 'activo']);
        });
    }
}
