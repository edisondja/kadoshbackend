<?php

namespace App\Services\Dgii;

use App\Config;
use App\Factura;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DgiiEcfService
{
    public function __construct(
        private DgiiAuthService $auth,
        private DgiiXmlSignerService $signer,
        private DgiiEcfBuilder $builder,
        private DgiiCertificateService $certs
    ) {
    }

    public function isEnabled(?Config $config = null): bool
    {
        $config = $config ?: Config::first();
        return $config && (bool) ($config->dgii_ecf_activo ?? false);
    }

    public function shouldAutoEmit(?Config $config = null): bool
    {
        $config = $config ?: Config::first();
        if (!$this->isEnabled($config)) {
            return false;
        }
        if ($config && isset($config->dgii_auto_emitir)) {
            return (bool) $config->dgii_auto_emitir;
        }
        return (bool) config('dgii.auto_emitir', true);
    }

    /**
     * Emite e-CF 32 para una factura (RFCE si monto < 250,000).
     */
    public function emitirFactura(int $facturaId, bool $force = false): array
    {
        if (!Schema::hasColumn('facturas', 'encf')) {
            throw new RuntimeException('Falta migración DGII e-CF. Ejecuta: php artisan migrate');
        }

        $config = Config::first();
        if (!$config) {
            throw new RuntimeException('No hay configuración de clínica.');
        }
        if (!$this->isEnabled($config) && !$force) {
            throw new RuntimeException('Facturación electrónica DGII está desactivada.');
        }

        $factura = Factura::with(['paciente', 'recibos'])->find($facturaId);
        if (!$factura) {
            throw new RuntimeException('Factura no encontrada.');
        }

        if (!$force && !empty($factura->encf) && in_array($factura->ecf_estado, ['Aceptado', 'Enviado', 'AceptadoCondicional'], true)) {
            return [
                'success' => true,
                'already_emitted' => true,
                'encf' => $factura->encf,
                'estado' => $factura->ecf_estado,
                'track_id' => $factura->ecf_track_id,
                'qr_url' => $factura->ecf_qr_url,
                'codigo_seguridad' => $factura->ecf_codigo_seguridad,
            ];
        }

        $rncEmisor = preg_replace('/\D+/', '', (string) ($config->rnc_clinica ?? ''));
        if ($rncEmisor === '') {
            throw new RuntimeException('Configura el RNC de la clínica antes de emitir e-CF.');
        }

        $razonEmisor = $config->nombre_clinica ?: ($config->nombre ?: 'Clinica');
        $paciente = $factura->paciente;
        $razonComprador = trim(($paciente->nombre ?? '') . ' ' . ($paciente->apellido ?? ''));
        if ($razonComprador === '') {
            $razonComprador = 'CONSUMIDOR FINAL';
        }
        $rncComprador = preg_replace('/\D+/', '', (string) ($paciente->cedula ?? ''));

        $items = $this->itemsFromFactura($facturaId);
        $montoTotal = round(array_sum(array_column($items, 'monto')), 2);
        if ($montoTotal <= 0) {
            throw new RuntimeException('El monto de la factura es 0; no se puede emitir e-CF.');
        }

        $tipoEcf = (string) ($config->dgii_tipo_ecf ?? '32');
        if ($tipoEcf !== '32') {
            // Primera versión: solo consumo electrónico 32
            $tipoEcf = '32';
        }

        $encf = $this->nextEncf($config, $tipoEcf);
        $now = now('America/Santo_Domingo');
        $fechaEmision = $now->format('d-m-Y');
        $fechaFirma = $now->format('d-m-Y H:i:s');

        $formaPago = $this->mapFormaPago($factura);
        $xml = $this->builder->buildEcf32([
            'encf' => $encf,
            'rnc_emisor' => $rncEmisor,
            'razon_emisor' => $razonEmisor,
            'direccion_emisor' => $config->direccion_clinica ?? '',
            'telefono_emisor' => $config->telefono_clinica ?? $config->telefono ?? '',
            'correo_emisor' => $config->email_clinica ?? $config->email ?? '',
            'fecha_emision' => $fechaEmision,
            'fecha_firma' => $fechaFirma,
            'fecha_vencimiento' => $config->dgii_fecha_vencimiento_secuencia ?: null,
            'tipo_ingresos' => $config->dgii_tipo_ingresos ?? config('dgii.tipo_ingresos', '01'),
            'tipo_pago' => 1,
            'forma_pago' => $formaPago,
            'rnc_comprador' => $rncComprador,
            'razon_comprador' => $razonComprador,
            'items' => $items,
            'itbis_modo' => $config->dgii_itbis_modo ?? config('dgii.itbis_modo', 'exento'),
            'numero_interno' => (string) $facturaId,
        ]);

        $signed = $this->signer->sign($xml, $config);
        $codigo = $this->signer->codigoSeguridad($signed);

        // Guardar XML firmado
        $xmlDir = storage_path('app/private/dgii/xml');
        if (!is_dir($xmlDir)) {
            mkdir($xmlDir, 0700, true);
        }
        $fileName = $rncEmisor . $encf . '.xml';
        $xmlPath = $xmlDir . '/' . $fileName;
        file_put_contents($xmlPath, $signed);

        $token = $this->auth->token($config);
        $useRfce = $montoTotal < (float) config('dgii.monto_rfce_max', 250000);

        if ($useRfce) {
            $rfce = $this->builder->buildRfceFromSignedEcf32($signed, $codigo);
            $rfcePath = $xmlDir . '/' . $rncEmisor . $encf . '_RFCE.xml';
            file_put_contents($rfcePath, $rfce);
            $response = $this->sendXml(
                $this->auth->base('recepcion_fc', $config) . '/api/recepcion/ecf',
                $token,
                $rfce,
                $rncEmisor . $encf . '.xml'
            );
        } else {
            $response = $this->sendXml(
                $this->auth->base('recepcion', $config) . '/api/facturaselectronicas',
                $token,
                $signed,
                $fileName
            );
        }

        $trackId = $response['trackId'] ?? $response['trackid'] ?? $response['TrackId'] ?? null;
        $estado = $response['estado'] ?? $response['Estado'] ?? ($trackId ? 'Enviado' : 'Desconocido');
        $mensaje = null;
        if (!empty($response['mensajes'])) {
            $mensaje = is_string($response['mensajes'])
                ? $response['mensajes']
                : json_encode($response['mensajes'], JSON_UNESCAPED_UNICODE);
        } elseif (!empty($response['mensaje'])) {
            $mensaje = (string) $response['mensaje'];
        }

        $qrUrl = $this->buildQrUrl($config, [
            'rnc_emisor' => $rncEmisor,
            'rnc_comprador' => $rncComprador,
            'encf' => $encf,
            'fecha_emision' => $fechaEmision,
            'monto_total' => $montoTotal,
            'fecha_firma' => $fechaFirma,
            'codigo_seguridad' => $codigo,
            'rfce' => $useRfce,
        ]);

        $factura->encf = $encf;
        $factura->tipoe_cf = $tipoEcf;
        $factura->ecf_estado = $estado;
        $factura->ecf_track_id = $trackId;
        $factura->ecf_codigo_seguridad = $codigo;
        $factura->ecf_fecha_firma = $fechaFirma;
        $factura->ecf_monto_total = $montoTotal;
        $factura->ecf_qr_url = $qrUrl;
        $factura->ecf_mensaje = $mensaje;
        $factura->ecf_enviado_at = now();
        $factura->save();

        return [
            'success' => true,
            'encf' => $encf,
            'estado' => $estado,
            'track_id' => $trackId,
            'codigo_seguridad' => $codigo,
            'qr_url' => $qrUrl,
            'monto_total' => $montoTotal,
            'rfce' => $useRfce,
            'respuesta' => $response,
            'xml_path' => $xmlPath,
        ];
    }

    public function probarAutenticacion(?Config $config = null): array
    {
        $config = $config ?: Config::first();
        $certInfo = $this->certs->validateReadable($config);
        $token = $this->auth->token($config, true);
        return [
            'ok' => true,
            'ambiente' => $this->auth->ambiente($config),
            'certificado' => $certInfo,
            'token_preview' => substr($token, 0, 24) . '…',
        ];
    }

    public function consultarEstado(string $trackId, ?Config $config = null): array
    {
        $config = $config ?: Config::first();
        $token = $this->auth->token($config);
        $url = $this->auth->base('consulta', $config) . '/api/Consultas/Estado?TrackId=' . urlencode($trackId);
        $resp = Http::timeout(45)
            ->withToken($token)
            ->acceptJson()
            ->get($url);

        if (!$resp->successful()) {
            throw new RuntimeException('Consulta DGII falló (' . $resp->status() . '): ' . $resp->body());
        }

        return $resp->json() ?? ['raw' => $resp->body()];
    }

    private function sendXml(string $url, string $token, string $xml, string $fileName): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dgii_xml_');
        file_put_contents($tmp, $xml);
        try {
            $resp = Http::timeout(60)
                ->withToken($token)
                ->attach('xml', file_get_contents($tmp), $fileName)
                ->post($url);
        } finally {
            @unlink($tmp);
        }

        if (!$resp->successful()) {
            Log::error('DGII recepción error', ['url' => $url, 'status' => $resp->status(), 'body' => $resp->body()]);
            throw new RuntimeException('Recepción DGII falló (' . $resp->status() . '): ' . $resp->body());
        }

        $json = $resp->json();
        if (!is_array($json)) {
            return ['raw' => $resp->body(), 'estado' => 'Enviado'];
        }
        return $json;
    }

    private function nextEncf(Config $config, string $tipo): string
    {
        return DB::transaction(function () use ($config, $tipo) {
            $locked = Config::where('id', $config->id)->lockForUpdate()->first();
            $field = $tipo === '31' ? 'dgii_secuencia_e31' : 'dgii_secuencia_e32';
            $seq = (int) ($locked->{$field} ?? 1);
            if ($seq < 1) {
                $seq = 1;
            }
            $locked->{$field} = $seq + 1;
            $locked->save();

            // E320000000001 — tipo + 10 dígitos
            return 'E' . $tipo . str_pad((string) $seq, 10, '0', STR_PAD_LEFT);
        });
    }

    private function itemsFromFactura(int $facturaId): array
    {
        $rows = DB::table('historial_ps')
            ->join('procedimientos', 'historial_ps.id_procedimiento', '=', 'procedimientos.id')
            ->where('historial_ps.id_factura', $facturaId)
            ->select(
                'procedimientos.nombre as nombre',
                'historial_ps.cantidad as cantidad',
                'historial_ps.total as total'
            )
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $cant = max((float) ($row->cantidad ?? 1), 1);
            $monto = round((float) ($row->total ?? 0), 2);
            if ($monto <= 0) {
                continue;
            }
            $items[] = [
                'nombre' => $row->nombre ?: 'Servicio odontológico',
                'cantidad' => $cant,
                'precio' => round($monto / $cant, 4),
                'monto' => $monto,
            ];
        }

        if (count($items) === 0) {
            $factura = Factura::find($facturaId);
            $pagado = (float) DB::table('recibos')->where('id_factura', $facturaId)->sum('monto');
            $descuentos = (float) DB::table('descuentos')->where('id_factura', $facturaId)->sum('monto');
            $saldo = (float) ($factura->precio_estatus ?? 0);
            $total = round($pagado + $descuentos + $saldo, 2);
            if ($total <= 0 && $factura && $factura->ecf_monto_total) {
                $total = (float) $factura->ecf_monto_total;
            }
            if ($total > 0) {
                $items[] = [
                    'nombre' => 'Servicios odontológicos',
                    'cantidad' => 1,
                    'precio' => $total,
                    'monto' => $total,
                ];
            }
        }

        return $items;
    }

    private function mapFormaPago(Factura $factura): int
    {
        // 1 Efectivo, 2 Cheque/Transferencia, 3 Tarjeta, 4 Venta a crédito, …
        $tipo = strtolower((string) ($factura->tipo_de_pago ?? ''));
        $ultimo = $factura->recibos()->orderByDesc('id')->first();
        if ($ultimo) {
            $tipo = strtolower((string) ($ultimo->tipo_de_pago ?? $tipo));
        }
        if (strpos($tipo, 'efectivo') !== false) {
            return 1;
        }
        if (strpos($tipo, 'cheque') !== false || strpos($tipo, 'transfer') !== false) {
            return 2;
        }
        if (strpos($tipo, 'tarjeta') !== false) {
            return 3;
        }
        return 1;
    }

    private function buildQrUrl(Config $config, array $d): string
    {
        if (!empty($d['rfce'])) {
            $base = $this->auth->base('qr_fc', $config);
            $qs = http_build_query([
                'RncEmisor' => $d['rnc_emisor'],
                'RncComprador' => $d['rnc_comprador'] ?: '00000000000',
                'ENCF' => $d['encf'],
                'FechaEmision' => $d['fecha_emision'],
                'MontoTotal' => number_format((float) $d['monto_total'], 2, '.', ''),
                'FechaFirma' => $d['fecha_firma'],
                'CodigoSeguridad' => $d['codigo_seguridad'],
            ]);
            return $base . '?' . $qs;
        }

        $base = $this->auth->base('qr_ecf', $config);
        $qs = http_build_query([
            'RncEmisor' => $d['rnc_emisor'],
            'RncComprador' => $d['rnc_comprador'] ?: '00000000000',
            'ENCF' => $d['encf'],
            'FechaEmision' => $d['fecha_emision'],
            'MontoTotal' => number_format((float) $d['monto_total'], 2, '.', ''),
            'FechaFirma' => $d['fecha_firma'],
            'CodigoSeguridad' => $d['codigo_seguridad'],
        ]);
        return $base . '?' . $qs;
    }
}
