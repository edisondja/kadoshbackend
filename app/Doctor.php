<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'numero_telefono',
        'especialidad',
        'sexo',
        'estado'
    ];

    public function cita(){

        return $this->hasMany(Cita::class);
        
    }

    public function salarios()
    {
        return $this->hasMany(SalarioDoctor::class);
    }

}
