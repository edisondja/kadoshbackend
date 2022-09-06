<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarEmailYFoto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


            Schema::table('pacientes', function (Blueprint $table) {
                $table->string("correo_electronico");
                $table->string("foto_paciente");
            });

       

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /*
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profession');
        });*/
    }
}
