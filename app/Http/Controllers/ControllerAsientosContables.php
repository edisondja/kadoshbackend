<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use App;

class ControllerAsientosContables extends Controller
{
    private function tablaExiste()
    {
        try {
            return DB::getSchemaBuilder()->hasTable('asientos_contables');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function cuentaPorTipoPago($tipo)
    {
        $t = strtolower((string) $tipo);
        if (strpos($t, 'efectivo') !== false) {
            return 'Caja';
        }
        if (strpos($t, 'transfer') !== false) {
            return 'Banco';
        }
        if (strpos($t, 'cheque') !== false) {
            return 'Banco';
        }
        if (strpos($t, 'tarjeta') !== false) {
            return 'Banco / Tarjeta';
        }
        if (strpos($t, 'mixto') !== false) {
            return 'Caja / Banco';
        }
        return 'Caja / Banco';
    }

    private function montoTotalFactura($facturaId, $saldoActual)
    {
        $pagado = (float) DB::table('recibos')->where('id_factura', $facturaId)->sum('monto');
        $descuentos = (float) DB::table('descuentos')->where('id_factura', $facturaId)->sum('monto');
        return (float) $saldoActual + $pagado + $descuentos;
    }

    private function parseFechaGasto($gasto)
    {
        if (!empty($gasto->created_at)) {
            try {
                return Carbon::parse($gasto->created_at);
            } catch (\Exception $e) {
            }
        }
        $raw = trim((string) ($gasto->fecha_registro ?? ''));
        if ($raw === '') {
            return Carbon::now();
        }
        $formatos = ['Y-m-d', 'Y-m-d H:i:s', 'ymd', 'd/m/Y', 'd-m-Y'];
        foreach ($formatos as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $raw);
            } catch (\Exception $e) {
            }
        }
        try {
            return Carbon::parse($raw);
        } catch (\Exception $e) {
            return Carbon::now();
        }
    }

    private function upsertAsiento(array $data)
    {
        if (!$this->tablaExiste()) {
            return false;
        }
        $now = Carbon::now();
        $payload = array_merge($data, [
            'automatico' => 1,
            'updated_at' => $now,
        ]);
        $existe = DB::table('asientos_contables')
            ->where('origen_tipo', $data['origen_tipo'])
            ->where('origen_id', $data['origen_id'])
            ->first();
        if ($existe) {
            DB::table('asientos_contables')
                ->where('id', $existe->id)
                ->update($payload);
            return 'actualizado';
        }
        $payload['created_at'] = $now;
        DB::table('asientos_contables')->insert($payload);
        return 'insertado';
    }

    private function ejecutarSincronizacionRetroactiva()
    {
        if (!$this->tablaExiste()) {
            return [
                'success' => false,
                'message' => 'La tabla asientos_contables no existe. Ejecute el SQL de migración.',
                'insertados' => 0,
                'actualizados' => 0,
            ];
        }

        $insertados = 0;
        $actualizados = 0;

        // Cobros — todos los recibos históricos
        $recibos = App\Recibo::with(['factura.paciente'])->orderBy('fecha_pago', 'asc')->get();
        foreach ($recibos as $recibo) {
            $paciente = optional(optional($recibo->factura)->paciente);
            $nombrePaciente = trim(($paciente->nombre ?? '') . ' ' . ($paciente->apellido ?? ''));
            $resultado = $this->upsertAsiento([
                'origen_tipo' => 'recibo',
                'origen_id' => (int) $recibo->id,
                'fecha' => Carbon::parse($recibo->fecha_pago)->format('Y-m-d H:i:s'),
                'tipo' => 'cobro',
                'tipo_label' => 'Cobro (ingreso)',
                'referencia' => $recibo->codigo_recibo,
                'descripcion' => $recibo->concepto_pago ?: 'Cobro de factura',
                'tercero' => $nombrePaciente ?: 'Paciente',
                'forma_pago' => $recibo->tipo_de_pago,
                'cuenta_debe' => $this->cuentaPorTipoPago($recibo->tipo_de_pago),
                'cuenta_haber' => 'Cuentas por cobrar',
                'monto' => (float) $recibo->monto,
                'naturaleza' => 'ingreso',
                'id_factura' => $recibo->id_factura,
                'saldo_pendiente' => null,
            ]);
            if ($resultado === 'insertado') {
                $insertados++;
            } elseif ($resultado === 'actualizado') {
                $actualizados++;
            }
        }

        // Gastos — todos los gastos históricos
        $gastos = DB::table('gastos')
            ->leftJoin('suplidors', 'gastos.suplidor_id', '=', 'suplidors.id')
            ->select('gastos.*', 'suplidors.nombre as suplidor_nombre')
            ->orderBy('gastos.id', 'asc')
            ->get();

        foreach ($gastos as $gasto) {
            $total = (float) $gasto->total;
            $itebis = (float) ($gasto->itebis ?? 0);
            $fecha = $this->parseFechaGasto($gasto);
            $resultado = $this->upsertAsiento([
                'origen_tipo' => 'gasto',
                'origen_id' => (int) $gasto->id,
                'fecha' => $fecha->format('Y-m-d H:i:s'),
                'tipo' => 'gasto',
                'tipo_label' => 'Gasto (egreso)',
                'referencia' => 'GASTO-' . $gasto->id,
                'descripcion' => $gasto->descripcion ?: ($gasto->tipo_de_gasto ?: 'Gasto operativo'),
                'tercero' => $gasto->suplidor_nombre ?: 'Suplidor',
                'forma_pago' => $gasto->tipo_de_pago,
                'cuenta_debe' => $gasto->tipo_de_gasto ?: 'Gastos operativos',
                'cuenta_haber' => $this->cuentaPorTipoPago($gasto->tipo_de_pago),
                'monto' => $total + $itebis,
                'naturaleza' => 'egreso',
                'id_factura' => null,
                'saldo_pendiente' => null,
            ]);
            if ($resultado === 'insertado') {
                $insertados++;
            } elseif ($resultado === 'actualizado') {
                $actualizados++;
            }
        }

        // Facturación — todas las facturas históricas
        $facturas = App\Factura::with(['paciente'])->orderBy('created_at', 'asc')->get();
        foreach ($facturas as $factura) {
            $montoFacturado = $this->montoTotalFactura($factura->id, $factura->precio_estatus);
            if ($montoFacturado <= 0) {
                continue;
            }
            $paciente = $factura->paciente;
            $nombrePaciente = trim(($paciente->nombre ?? '') . ' ' . ($paciente->apellido ?? ''));
            $tipoFactura = ($factura->tipo_factura ?? 'servicio') === 'venta' ? 'Venta' : 'Servicio';
            $resultado = $this->upsertAsiento([
                'origen_tipo' => 'factura',
                'origen_id' => (int) $factura->id,
                'fecha' => Carbon::parse($factura->created_at)->format('Y-m-d H:i:s'),
                'tipo' => 'facturacion',
                'tipo_label' => 'Facturación (CxC)',
                'referencia' => 'FACT-' . $factura->id,
                'descripcion' => 'Factura ' . $tipoFactura . ' generada',
                'tercero' => $nombrePaciente ?: 'Paciente',
                'forma_pago' => $factura->tipo_de_pago,
                'cuenta_debe' => 'Cuentas por cobrar',
                'cuenta_haber' => $tipoFactura === 'Venta' ? 'Ingresos por ventas' : 'Ingresos por servicios',
                'monto' => $montoFacturado,
                'naturaleza' => 'cxc',
                'id_factura' => $factura->id,
                'saldo_pendiente' => (float) $factura->precio_estatus,
            ]);
            if ($resultado === 'insertado') {
                $insertados++;
            } elseif ($resultado === 'actualizado') {
                $actualizados++;
            }
        }

        // Descuentos — reducen CxC
        $descuentos = DB::table('descuentos')->orderBy('created_at', 'asc')->get();
        foreach ($descuentos as $descuento) {
            $factura = App\Factura::with('paciente')->find($descuento->id_factura);
            $paciente = optional(optional($factura)->paciente);
            $nombrePaciente = trim(($paciente->nombre ?? '') . ' ' . ($paciente->apellido ?? ''));
            $fecha = $descuento->created_at ? Carbon::parse($descuento->created_at) : Carbon::now();
            $resultado = $this->upsertAsiento([
                'origen_tipo' => 'descuento',
                'origen_id' => (int) $descuento->id,
                'fecha' => $fecha->format('Y-m-d H:i:s'),
                'tipo' => 'descuento',
                'tipo_label' => 'Descuento (CxC)',
                'referencia' => 'DESC-' . $descuento->id,
                'descripcion' => $descuento->comentario ?: 'Descuento aplicado a factura',
                'tercero' => $nombrePaciente ?: 'Paciente',
                'forma_pago' => null,
                'cuenta_debe' => 'Descuentos concedidos',
                'cuenta_haber' => 'Cuentas por cobrar',
                'monto' => (float) $descuento->monto,
                'naturaleza' => 'descuento',
                'id_factura' => $descuento->id_factura,
                'saldo_pendiente' => null,
            ]);
            if ($resultado === 'insertado') {
                $insertados++;
            } elseif ($resultado === 'actualizado') {
                $actualizados++;
            }
        }

        return [
            'success' => true,
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'total' => DB::table('asientos_contables')->count(),
        ];
    }

    public function sincronizar(Request $request)
    {
        $resultado = $this->ejecutarSincronizacionRetroactiva();
        if (!$resultado['success']) {
            return response()->json($resultado, 400);
        }
        return response()->json($resultado);
    }

    private function parseRangoFechas(Request $request)
    {
        $retroactivo = filter_var($request->query('retroactivo', false), FILTER_VALIDATE_BOOLEAN);
        $desde = $request->query('fecha_desde');
        $hasta = $request->query('fecha_hasta');

        if ($retroactivo || (!$desde && !$hasta)) {
            return [null, null, true];
        }

        if (!$desde || !$hasta) {
            $hasta = Carbon::today()->endOfDay();
            $desde = Carbon::today()->subYears(2)->startOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        if ($desde->gt($hasta)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha inicial no puede ser posterior a la final.',
            ], 422);
        }

        return [$desde, $hasta, false];
    }

    private function movimientosDesdeTabla($desde, $hasta, $tipoFiltro, $retroactivo)
    {
        $query = DB::table('asientos_contables')->orderBy('fecha', 'desc');

        if (!$retroactivo && $desde && $hasta) {
            $query->whereBetween('fecha', [
                $desde->format('Y-m-d H:i:s'),
                $hasta->format('Y-m-d H:i:s'),
            ]);
        }

        if ($tipoFiltro !== 'todos') {
            $query->where('tipo', $tipoFiltro);
        }

        return $query->get()->map(function ($row) {
            return [
                'id' => $row->origen_tipo . '-' . $row->origen_id,
                'fecha' => Carbon::parse($row->fecha)->format('d/m/Y H:i'),
                'tipo' => $row->tipo,
                'tipo_label' => $row->tipo_label,
                'referencia' => $row->referencia,
                'descripcion' => $row->descripcion,
                'tercero' => $row->tercero,
                'forma_pago' => $row->forma_pago,
                'cuenta_debe' => $row->cuenta_debe,
                'cuenta_haber' => $row->cuenta_haber,
                'monto' => (float) $row->monto,
                'naturaleza' => $row->naturaleza,
                'id_factura' => $row->id_factura,
                'saldo_pendiente' => $row->saldo_pendiente !== null ? (float) $row->saldo_pendiente : null,
                'automatico' => (bool) $row->automatico,
            ];
        })->values()->all();
    }

    private function calcularResumen(array $movimientos)
    {
        $totalIngresos = 0;
        $totalGastos = 0;
        $totalFacturado = 0;
        $totalDescuentos = 0;
        $porTipo = [
            'cobro' => 0,
            'gasto' => 0,
            'facturacion' => 0,
            'descuento' => 0,
        ];

        foreach ($movimientos as $m) {
            $porTipo[$m['tipo']] = ($porTipo[$m['tipo']] ?? 0) + 1;
            if ($m['naturaleza'] === 'ingreso') {
                $totalIngresos += (float) $m['monto'];
            } elseif ($m['naturaleza'] === 'egreso') {
                $totalGastos += (float) $m['monto'];
            } elseif ($m['naturaleza'] === 'cxc') {
                $totalFacturado += (float) $m['monto'];
            } elseif ($m['naturaleza'] === 'descuento') {
                $totalDescuentos += (float) $m['monto'];
            }
        }

        $cxcPendiente = (float) DB::table('facturas')->where('precio_estatus', '>', 0)->sum('precio_estatus');

        return [
            'total_ingresos' => round($totalIngresos, 2),
            'total_gastos' => round($totalGastos, 2),
            'total_facturado' => round($totalFacturado, 2),
            'total_descuentos' => round($totalDescuentos, 2),
            'saldo_neto' => round($totalIngresos - $totalGastos, 2),
            'cuentas_por_cobrar_pendiente' => round($cxcPendiente, 2),
            'cantidad_movimientos' => count($movimientos),
            'por_tipo' => $porTipo,
        ];
    }

    public function listar(Request $request)
    {
        $rango = $this->parseRangoFechas($request);
        if ($rango instanceof \Illuminate\Http\JsonResponse) {
            return $rango;
        }
        list($desde, $hasta, $retroactivo) = $rango;
        $tipoFiltro = strtolower(trim((string) $request->query('tipo', 'todos')));

        if (!$this->tablaExiste()) {
            return response()->json([
                'success' => false,
                'message' => 'Ejecute database/sql/2026_08_29_asientos_contables.sql en la base del tenant.',
            ], 400);
        }

        $totalEnTabla = DB::table('asientos_contables')->count();
        if ($totalEnTabla === 0 || filter_var($request->query('resincronizar', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->ejecutarSincronizacionRetroactiva();
        }

        $movimientos = $this->movimientosDesdeTabla($desde, $hasta, $tipoFiltro, $retroactivo);
        $resumen = $this->calcularResumen($movimientos);

        return response()->json([
            'success' => true,
            'retroactivo' => $retroactivo,
            'fecha_desde' => $retroactivo ? null : $desde->format('Y-m-d'),
            'fecha_hasta' => $retroactivo ? null : $hasta->format('Y-m-d'),
            'movimientos' => $movimientos,
            'resumen' => $resumen,
            'sincronizado' => true,
            'total_registrados' => DB::table('asientos_contables')->count(),
        ]);
    }
}
