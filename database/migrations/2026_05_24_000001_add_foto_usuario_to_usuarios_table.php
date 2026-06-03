<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFotoUsuarioToUsuariosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('usuarios', 'foto_usuario')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('foto_usuario')->nullable()->after('apellido');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('usuarios', 'foto_usuario')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('foto_usuario');
            });
        }
    }
}
