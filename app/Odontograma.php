<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Odontograma extends Model
{
    protected $fillable = [
        'paciente_id',
        'diente_numero',
        'superficie',
        'estado',
        'procedimiento_id',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function procedimiento()
    {
        return $this->belongsTo(Procedimiento::class);
    }
}
 