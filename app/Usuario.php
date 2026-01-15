<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    
    protected $fillable = [
        'usuario',
        'clave',
        'roll',
        'nombre',
        'apellido'
    ];

    public function pagosMensuales()
    {
        return $this->hasMany(PagoMensual::class);
    }
}
