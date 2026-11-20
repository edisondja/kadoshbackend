<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDgiiEcfFields extends Migration
{
    public function up()
    {
        Schema::table('configs', function (Blueprint $table) {
            if (!Schema::hasColumn('configs', 'dgii_ecf_activo')) {
                $table->boolean('dgii_ecf_activo')->default(false)->after('mostrar_firma_documentos');
            }
            if (!Schema::hasColumn('configs', 'dgii_ambiente')) {
                $table->string('dgii_ambiente', 20)->default('testecf')->after('dgii_ecf_activo');
            }
            if (!Schema::hasColumn('configs', 'dgii_tipo_ecf')) {
                $table->string('dgii_tipo_ecf', 2)->default('32')->after('dgii_ambiente');
            }
            if (!Schema::hasColumn('configs', 'dgii_tipo_ingresos')) {
                $table->string('dgii_tipo_ingresos', 2)->default('01')->after('dgii_tipo_ecf');
            }
            if (!Schema::hasColumn('configs', 'dgii_itbis_modo')) {
                $table->string('dgii_itbis_modo', 10)->default('exento')->after('dgii_tipo_ingresos');
            }
            if (!Schema::hasColumn('configs', 'dgii_secuencia_e32')) {
                $table->unsignedBigInteger('dgii_secuencia_e32')->default(1)->after('dgii_itbis_modo');
            }
            if (!Schema::hasColumn('configs', 'dgii_secuencia_e31')) {
                $table->unsignedBigInteger('dgii_secuencia_e31')->default(1)->after('dgii_secuencia_e32');
            }
            if (!Schema::hasColumn('configs', 'dgii_fecha_vencimiento_secuencia')) {
                $table->string('dgii_fecha_vencimiento_secuencia', 10)->nullable()->after('dgii_secuencia_e31');
            }
            if (!Schema::hasColumn('configs', 'dgii_auto_emitir')) {
                $table->boolean('dgii_auto_emitir')->default(true)->after('dgii_fecha_vencimiento_secuencia');
            }
            if (!Schema::hasColumn('configs', 'dgii_cert_password')) {
                $table->text('dgii_cert_password')->nullable()->after('dgii_auto_emitir');
            }
            if (!Schema::hasColumn('configs', 'dgii_cert_loaded')) {
                $table->boolean('dgii_cert_loaded')->default(false)->after('dgii_cert_password');
            }
        });

        Schema::table('facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas', 'encf')) {
                $table->string('encf', 13)->nullable()->after('tipo_de_pago');
            }
            if (!Schema::hasColumn('facturas', 'tipoe_cf')) {
                $table->string('tipoe_cf', 2)->nullable()->after('encf');
            }
            if (!Schema::hasColumn('facturas', 'ecf_estado')) {
                $table->string('ecf_estado', 40)->nullable()->after('tipoe_cf');
            }
            if (!Schema::hasColumn('facturas', 'ecf_track_id')) {
                $table->string('ecf_track_id', 64)->nullable()->after('ecf_estado');
            }
            if (!Schema::hasColumn('facturas', 'ecf_codigo_seguridad')) {
                $table->string('ecf_codigo_seguridad', 12)->nullable()->after('ecf_track_id');
            }
            if (!Schema::hasColumn('facturas', 'ecf_fecha_firma')) {
                $table->string('ecf_fecha_firma', 19)->nullable()->after('ecf_codigo_seguridad');
            }
            if (!Schema::hasColumn('facturas', 'ecf_monto_total')) {
                $table->decimal('ecf_monto_total', 14, 2)->nullable()->after('ecf_fecha_firma');
            }
            if (!Schema::hasColumn('facturas', 'ecf_qr_url')) {
                $table->text('ecf_qr_url')->nullable()->after('ecf_monto_total');
            }
            if (!Schema::hasColumn('facturas', 'ecf_mensaje')) {
                $table->text('ecf_mensaje')->nullable()->after('ecf_qr_url');
            }
            if (!Schema::hasColumn('facturas', 'ecf_enviado_at')) {
                $table->timestamp('ecf_enviado_at')->nullable()->after('ecf_mensaje');
            }
        });
    }

    public function down()
    {
        Schema::table('configs', function (Blueprint $table) {
            $cols = [
                'dgii_ecf_activo', 'dgii_ambiente', 'dgii_tipo_ecf', 'dgii_tipo_ingresos',
                'dgii_itbis_modo', 'dgii_secuencia_e32', 'dgii_secuencia_e31',
                'dgii_fecha_vencimiento_secuencia', 'dgii_auto_emitir',
                'dgii_cert_password', 'dgii_cert_loaded',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('configs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('facturas', function (Blueprint $table) {
            $cols = [
                'encf', 'tipoe_cf', 'ecf_estado', 'ecf_track_id', 'ecf_codigo_seguridad',
                'ecf_fecha_firma', 'ecf_monto_total', 'ecf_qr_url', 'ecf_mensaje', 'ecf_enviado_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('facturas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
