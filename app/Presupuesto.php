<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    
    protected $fillable = [
        'nombre',
        'factura',
        'paciente_id',
        'doctor_id',
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

    public function doctor(){

        return $this->belongsTo(Doctor::class,'doctor_id');

    }

}
