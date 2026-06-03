<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchemaSqlAppliedTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('schema_sql_applied')) {
            Schema::create('schema_sql_applied', function (Blueprint $table) {
                $table->string('filename', 255);
                $table->timestamp('applied_at')->nullable();
                $table->primary('filename');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('schema_sql_applied');
    }
}
