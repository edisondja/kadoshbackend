<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    
    protected $fillable = [
        'pdf',
        'email',
        'asunto',
        'nombre_compania',
        'logo_compania',
        'direccion_compania',
        'telefono_compania',
    ];


    public function paciente(){

        return $this->belongsTo(Paciente::class,'paciente_id');

    }

}
