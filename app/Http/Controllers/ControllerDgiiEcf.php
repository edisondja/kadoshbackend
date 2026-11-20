<?php

namespace App\Http\Controllers;

use App\Config;
use App\Factura;
use App\Services\Dgii\DgiiCertificateService;
use App\Services\Dgii\DgiiEcfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ControllerDgiiEcf extends Controller
{
    public function estado(DgiiCertificateService $certs)
    {
        $config = Config::first();
        return response()->json([
            'activo' => (bool) ($config->dgii_ecf_activo ?? false),
            'ambiente' => $config->dgii_ambiente ?? config('dgii.ambiente'),
            'tipo_ecf' => $config->dgii_tipo_ecf ?? '32',
            'tipo_ingresos' => $config->dgii_tipo_ingresos ?? '01',
            'itbis_modo' => $config->dgii_itbis_modo ?? 'exento',
            'secuencia_e32' => (int) ($config->dgii_secuencia_e32 ?? 1),
            'secuencia_e31' => (int) ($config->dgii_secuencia_e31 ?? 1),
            'fecha_vencimiento_secuencia' => $config->dgii_fecha_vencimiento_secuencia ?? null,
            'auto_emitir' => (bool) ($config->dgii_auto_emitir ?? true),
            'cert_loaded' => $certs->certExists() || (bool) ($config->dgii_cert_loaded ?? false),
            'tiene_password' => !empty($config->dgii_cert_password) || !empty(config('dgii.cert_password')),
            'rnc_clinica' => $config->rnc_clinica ?? null,
            'migracion_ok' => Schema::hasColumn('facturas', 'encf'),
        ]);
    }

    public function probarAuth(DgiiEcfService $ecf)
    {
        try {
            return response()->json($ecf->probarAutenticacion());
        } catch (\Throwable $e) {
            Log::error('DGII auth test: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function subirCertificado(Request $request, DgiiCertificateService $certs)
    {
        $request->validate([
            'certificado' => 'required|file|max:5120',
            'password' => 'nullable|string|max:255',
        ]);

        try {
            $path = $certs->storeUploadedCert($request->file('certificado'));
            if ($request->filled('password')) {
                $certs->storePassword($request->input('password'));
            }
            // Validar si hay password
            $info = null;
            try {
                $info = $certs->validateReadable();
            } catch (\Throwable $e) {
                $info = ['ok' => false, 'message' => $e->getMessage()];
            }

            return response()->json([
                'success' => true,
                'path' => 'storage/app/private/dgii/identity.p12',
                'certificado' => $info,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function guardarPassword(Request $request, DgiiCertificateService $certs)
    {
        $request->validate(['password' => 'required|string|max:255']);
        try {
            $certs->storePassword($request->input('password'));
            $info = $certs->validateReadable();
            return response()->json(['success' => true, 'certificado' => $info]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function emitir(Request $request, $id_factura, DgiiEcfService $ecf)
    {
        try {
            $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
            $result = $ecf->emitirFactura((int) $id_factura, $force);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('DGII emitir factura ' . $id_factura . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function consultarTrack($trackId, DgiiEcfService $ecf)
    {
        try {
            $data = $ecf->consultarEstado($trackId);
            // Actualizar factura si existe
            if (Schema::hasColumn('facturas', 'ecf_track_id')) {
                $factura = Factura::where('ecf_track_id', $trackId)->first();
                if ($factura) {
                    $estado = $data['estado'] ?? $data['Estado'] ?? null;
                    if ($estado) {
                        $factura->ecf_estado = $estado;
                        $factura->save();
                    }
                }
            }
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function deFactura($id_factura)
    {
        if (!Schema::hasColumn('facturas', 'encf')) {
            return response()->json(['encf' => null]);
        }
        $f = Factura::find($id_factura);
        if (!$f) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }
        return response()->json([
            'encf' => $f->encf,
            'tipoe_cf' => $f->tipoe_cf,
            'estado' => $f->ecf_estado,
            'track_id' => $f->ecf_track_id,
            'codigo_seguridad' => $f->ecf_codigo_seguridad,
            'fecha_firma' => $f->ecf_fecha_firma,
            'monto_total' => $f->ecf_monto_total,
            'qr_url' => $f->ecf_qr_url,
            'mensaje' => $f->ecf_mensaje,
            'enviado_at' => $f->ecf_enviado_at,
        ]);
    }
}
