<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStorageQuotaToTenantsTable extends Migration
{
    public function up()
    {
        Schema::connection('mysql')->table('tenants', function (Blueprint $table) {
            if (!Schema::connection('mysql')->hasColumn('tenants', 'storage_quota_gb')) {
                $table->decimal('storage_quota_gb', 8, 2)->default(10)->after('notas');
            }
            if (!Schema::connection('mysql')->hasColumn('tenants', 'storage_used_bytes')) {
                $table->unsignedBigInteger('storage_used_bytes')->default(0)->after('storage_quota_gb');
            }
        });
    }

    public function down()
    {
        Schema::connection('mysql')->table('tenants', function (Blueprint $table) {
            if (Schema::connection('mysql')->hasColumn('tenants', 'storage_used_bytes')) {
                $table->dropColumn('storage_used_bytes');
            }
            if (Schema::connection('mysql')->hasColumn('tenants', 'storage_quota_gb')) {
                $table->dropColumn('storage_quota_gb');
            }
        });
    }
}
