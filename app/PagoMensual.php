<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PagoMensual extends Model
{
    protected $table = 'pagos_mensuales';
    
    protected $fillable = [
        'usuario_id',
        'fecha_pago',
        'fecha_vencimiento',
        'monto',
        'estado',
        'comentarios',
        'dias_gracia'
    ];

    protected $dates = [
        'fecha_pago',
        'fecha_vencimiento',
        'created_at',
        'updated_at'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Verifica si el pago está próximo a vencer (dentro de los días de gracia)
     */
    public function estaPorVencer()
    {
        $hoy = now();
        $fechaAlerta = $this->fecha_vencimiento->subDays($this->dias_gracia);
        return $hoy >= $fechaAlerta && $hoy < $this->fecha_vencimiento && $this->estado !== 'pagado';
    }

    /**
     * Obtiene los días restantes hasta el vencimiento
     */
    public function diasRestantes()
    {
        $hoy = now();
        $vencimiento = $this->fecha_vencimiento;
        return $hoy->diffInDays($vencimiento, false);
    }
}
