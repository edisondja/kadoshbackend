<?php

namespace App\Services;

class NominaEmpleadoService
{
    /** Tope salarial TSS (10 salarios mínimos, aprox.) */
    const TOPE_TSS_MENSUAL = 210000.00;

    /** Exento mensual ISR (416220 anual / 12) */
    const ISR_EXENTO_MENSUAL = 34685.00;

    /**
     * Calcula deducciones de nómina para depósito.
     *
     * @param float $salarioBruto
     * @param array $config aplica_afp, aplica_sfs, aplica_isr, porcentaje_afp, porcentaje_sfs, porcentaje_isr, otros_descuentos
     * @param array|null $override Deducciones manuales al registrar pago (opcional)
     */
    public function calcularDeducciones($salarioBruto, array $config, array $override = null)
    {
        $bruto = round(max(0, floatval($salarioBruto)), 2);
        $baseTss = min($bruto, self::TOPE_TSS_MENSUAL);

        $aplicaAfp = !empty($config['aplica_afp']);
        $aplicaSfs = !empty($config['aplica_sfs']);
        $aplicaIsr = !empty($config['aplica_isr']);

        $pctAfp = floatval($config['porcentaje_afp'] ?? 2.87);
        $pctSfs = floatval($config['porcentaje_sfs'] ?? 3.04);
        $pctIsr = isset($config['porcentaje_isr']) && $config['porcentaje_isr'] !== null && $config['porcentaje_isr'] !== ''
            ? floatval($config['porcentaje_isr'])
            : null;
        $otrosFijos = round(floatval($config['otros_descuentos'] ?? 0), 2);

        if (is_array($override)) {
            $aplicaAfp = array_key_exists('aplica_afp', $override) ? !empty($override['aplica_afp']) : $aplicaAfp;
            $aplicaSfs = array_key_exists('aplica_sfs', $override) ? !empty($override['aplica_sfs']) : $aplicaSfs;
            $aplicaIsr = array_key_exists('aplica_isr', $override) ? !empty($override['aplica_isr']) : $aplicaIsr;
            if (isset($override['porcentaje_afp'])) {
                $pctAfp = floatval($override['porcentaje_afp']);
            }
            if (isset($override['porcentaje_sfs'])) {
                $pctSfs = floatval($override['porcentaje_sfs']);
            }
            if (array_key_exists('porcentaje_isr', $override)) {
                $pctIsr = $override['porcentaje_isr'] !== null && $override['porcentaje_isr'] !== ''
                    ? floatval($override['porcentaje_isr'])
                    : null;
            }
            if (isset($override['otros_descuentos'])) {
                $otrosFijos = round(floatval($override['otros_descuentos']), 2);
            }
        }

        $afp = $aplicaAfp ? round($baseTss * $pctAfp / 100, 2) : 0;
        $sfs = $aplicaSfs ? round($baseTss * $pctSfs / 100, 2) : 0;

        $isr = 0;
        if ($aplicaIsr) {
            if ($pctIsr !== null) {
                $isr = round($bruto * $pctIsr / 100, 2);
            } else {
                $imponibleIsr = max(0, $bruto - $afp - $sfs);
                $isr = $this->calcularIsrMensualDgii($imponibleIsr);
            }
        }

        $otros = $otrosFijos;
        $totalDeducciones = round($afp + $sfs + $isr + $otros, 2);
        $neto = round(max(0, $bruto - $totalDeducciones), 2);

        return [
            'bruto' => $bruto,
            'afp' => $afp,
            'sfs' => $sfs,
            'isr' => $isr,
            'otros_descuentos' => $otros,
            'total_deducciones' => $totalDeducciones,
            'neto_deposito' => $neto,
            'aplica_afp' => $aplicaAfp,
            'aplica_sfs' => $aplicaSfs,
            'aplica_isr' => $aplicaIsr,
            'porcentaje_afp' => $pctAfp,
            'porcentaje_sfs' => $pctSfs,
            'porcentaje_isr' => $pctIsr,
        ];
    }

    /**
     * Tabla ISR mensual simplificada (DGII RD).
     */
    private function calcularIsrMensualDgii($salarioImponible)
    {
        $imponible = round(floatval($salarioImponible), 2);
        if ($imponible <= self::ISR_EXENTO_MENSUAL) {
            return 0;
        }

        $exceso = $imponible - self::ISR_EXENTO_MENSUAL;
        $tramo1 = 17342.42; // 15%
        $tramo2 = 20232.83; // 20%
        $isr = 0;

        if ($exceso <= $tramo1) {
            return round($exceso * 0.15, 2);
        }
        $isr = round($tramo1 * 0.15, 2);
        $exceso -= $tramo1;

        if ($exceso <= $tramo2) {
            return round($isr + $exceso * 0.20, 2);
        }
        $isr += round($tramo2 * 0.20, 2);
        $exceso -= $tramo2;

        return round($isr + $exceso * 0.25, 2);
    }
}
