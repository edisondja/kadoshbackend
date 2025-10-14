<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FichaMedica extends Model
{

    // Relación con el modelo Paciente
    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

}
