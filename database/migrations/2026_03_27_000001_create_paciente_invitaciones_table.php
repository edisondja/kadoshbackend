<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePacienteInvitacionesTable extends Migration
{
    public function up()
    {
        Schema::create('paciente_invitaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token', 80)->unique();
            $table->unsignedInteger('id_doctor');
            $table->string('telefono_destino', 40)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paciente_invitaciones');
    }
}
