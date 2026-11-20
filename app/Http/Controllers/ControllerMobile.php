<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App;
use App\Services\StorageQuotaService;

class ControllerMobile extends Controller
{
    /**
     * Resumen para dashboard / cabecera de la app iOS.
     */
    public function dashboard(Request $request)
    {
        $hoyInicio = Carbon::today()->startOfDay();
        $hoyFin = Carbon::today()->endOfDay();

        $pacientesTotal = App\Paciente::count();

        $citasHoy = App\Cita::with(['paciente', 'doctor'])
            ->whereBetween('inicio', [$hoyInicio, $hoyFin])
            ->orderBy('inicio', 'asc')
            ->get();

        $proximas = App\Cita::with(['paciente', 'doctor'])
            ->where('inicio', '>=', Carbon::now())
            ->orderBy('inicio', 'asc')
            ->limit(8)
            ->get();

        $ingresosHoy = 0;
        try {
            // Laravel 5.5: whereDate/orWhereDate requieren operador (3 args)
            $hoy = Carbon::today()->toDateString();
            $ingresosHoy = (float) App\Recibo::where(function ($q) use ($hoy) {
                $q->whereDate('fecha_pago', '=', $hoy)
                    ->orWhereDate('created_at', '=', $hoy);
            })->sum('monto');
        } catch (\Exception $e) {
            try {
                $hoy = Carbon::today()->toDateString();
                $ingresosHoy = (float) App\Recibo::whereDate('created_at', '=', $hoy)->sum('monto');
            } catch (\Exception $e2) {
                $ingresosHoy = 0;
            }
        }

        $clinica = $this->clinicaPayload();

        return response()->json([
            'ok' => true,
            'clinica' => $clinica,
            'almacenamiento' => app(StorageQuotaService::class)->snapshot(),
            'resumen' => [
                'pacientes_total' => $pacientesTotal,
                'citas_hoy' => $citasHoy->count(),
                'ingresos_hoy' => $ingresosHoy,
            ],
            'citas_hoy' => $citasHoy,
            'proximas_citas' => $proximas,
        ]);
    }

    /**
     * Datos de clínica (nombre + logo) + almacenamiento para la app móvil.
     */
    public function clinica(Request $request)
    {
        return response()->json([
            'ok' => true,
            'clinica' => $this->clinicaPayload(),
            'almacenamiento' => app(StorageQuotaService::class)->snapshot(),
        ]);
    }

    private function clinicaPayload()
    {
        $config = null;
        try {
            $config = App\Config::orderBy('id', 'asc')->first();
        } catch (\Exception $e) {
            $config = null;
        }

        if (!$config) {
            return [
                'nombre' => null,
                'logo' => null,
            ];
        }

        $logo = $config->ruta_logo ?? null;
        if ($logo && strpos($logo, 'http') === false) {
            $logo = app(\App\Services\StorageQuotaService::class)->publicUrl(ltrim($logo, '/'));
        }

        return [
            'nombre' => $config->nombre_clinica ?? $config->nombre ?? null,
            'logo' => $logo,
        ];
    }
}
