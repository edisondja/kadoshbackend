<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminUsersTable extends Migration
{
    /**
     * Tabla en la base de datos maestra (mysql). Administradores del sistema multi-tenant.
     */
    public function up()
    {
        Schema::connection('mysql')->create('admin_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('usuario', 100)->unique();
            $table->string('password');
            $table->string('nombre', 255);
            $table->string('apellido', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('mysql')->dropIfExists('admin_users');
    }
}
