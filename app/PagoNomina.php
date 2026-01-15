<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PagoNomina extends Model
{
    protected $table = 'pagos_nomina';
    
    protected $fillable = [
        'doctor_id',
        'empleado_id',
        'fecha_pago',
        'periodo_inicio',
        'periodo_fin',
        'monto_comisiones',
        'salario_base',
        'total_pago',
        'estado',
        'comentarios',
        'tipo'
    ];

    protected $dates = [
        'fecha_pago',
        'periodo_inicio',
        'periodo_fin',
        'created_at',
        'updated_at'
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
