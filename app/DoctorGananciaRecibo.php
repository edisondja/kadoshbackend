<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DoctorGananciaRecibo extends Model
{
    protected $table = 'doctor_ganancias_recibos';
    
    protected $fillable = [
        'id_recibo',
        'id_doctor',
        'ganancia_doctor',
        'ganancia_clinica',
        'observaciones'
    ];

    protected $casts = [
        'ganancia_doctor' => 'decimal:2',
        'ganancia_clinica' => 'decimal:2'
    ];

    public function recibo()
    {
        return $this->belongsTo(Recibo::class, 'id_recibo');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }
}
