<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('subdominio', 100)->unique();
            $table->string('database_name', 255);
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('bloqueado')->default(false);
            $table->text('notas')->nullable();
            $table->string('contacto_nombre', 255)->nullable();
            $table->string('contacto_email', 255)->nullable();
            $table->string('contacto_telefono', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tenants');
    }
}
