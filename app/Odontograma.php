<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Odontograma extends Model
{
    protected $fillable = [
        'paciente_id',
        'doctor_id',
        'id_doctor', // Para compatibilidad con producción
        'dibujo_odontograma',
        'estado',
    ];

    public function detalles()
    {
        return $this->hasMany(Odontograma_detalles::class);
    }


    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function procedimiento()
    {
        return $this->belongsTo(Procedimiento::class);
    }
}
 