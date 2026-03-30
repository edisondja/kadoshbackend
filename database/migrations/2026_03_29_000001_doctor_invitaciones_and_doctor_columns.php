<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DoctorInvitacionesAndDoctorColumns extends Migration
{
    public function up()
    {
        Schema::create('doctor_invitaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token', 80)->unique();
            $table->unsignedInteger('id_doctor');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'correo_electronico')) {
                $table->string('correo_electronico', 191)->nullable();
            }
            if (!Schema::hasColumn('doctors', 'id_usuario')) {
                $table->unsignedInteger('id_usuario')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('doctor_invitaciones');

        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'correo_electronico')) {
                $table->dropColumn('correo_electronico');
            }
            if (Schema::hasColumn('doctors', 'id_usuario')) {
                $table->dropColumn('id_usuario');
            }
        });
    }
}
