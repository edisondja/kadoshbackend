<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'movil',
        'direccion',
        'salario',
        'usuario_id',
        'activo',
        'aplica_afp',
        'aplica_sfs',
        'aplica_isr',
        'porcentaje_afp',
        'porcentaje_sfs',
        'porcentaje_isr',
        'otros_descuentos',
        'comentarios_nomina',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'aplica_afp' => 'boolean',
        'aplica_sfs' => 'boolean',
        'aplica_isr' => 'boolean',
        'salario' => 'float',
        'porcentaje_afp' => 'float',
        'porcentaje_sfs' => 'float',
        'porcentaje_isr' => 'float',
        'otros_descuentos' => 'float',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
