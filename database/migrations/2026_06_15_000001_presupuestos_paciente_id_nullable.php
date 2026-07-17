<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PresupuestosPacienteIdNullable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('presupuestos')) {
            return;
        }

        $fkName = $this->findPacienteForeignKey();
        if ($fkName) {
            Schema::table('presupuestos', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        }

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->unsignedInteger('paciente_id')->nullable()->change();
        });
    }

    public function down()
    {
        if (!Schema::hasTable('presupuestos')) {
            return;
        }

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->unsignedInteger('paciente_id')->nullable(false)->change();
            $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('cascade');
        });
    }

    private function findPacienteForeignKey()
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$db, 'presupuestos', 'paciente_id']
        );

        return $row->name ?? null;
    }
}
