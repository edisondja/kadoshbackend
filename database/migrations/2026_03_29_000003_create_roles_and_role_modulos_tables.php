<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesAndRoleModulosTables extends Migration
{
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('role_modulos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_rol');
            $table->string('modulo', 80);
            $table->boolean('permitido')->default(true);
            $table->timestamps();
            $table->unique(['id_rol', 'modulo']);
        });

        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'id_rol')) {
                $table->unsignedInteger('id_rol')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'id_rol')) {
                $table->dropColumn('id_rol');
            }
        });
        Schema::dropIfExists('role_modulos');
        Schema::dropIfExists('roles');
    }
}
