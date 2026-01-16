<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FichaMedica extends Model
{

    protected $fillable = [
        'paciente_id',
        'direccion',
        'ocupacion',
        'tratamiento_actual',
        'tratamiento_detalle',
        'enfermedades',
        'medicamentos',
        'tabaquismo',
        'alcohol',
        'otros_habitos',
        'alergias',
        'alergias_detalle',
        'observaciones'
    ];


    // Relación con el modelo Paciente
    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

}
