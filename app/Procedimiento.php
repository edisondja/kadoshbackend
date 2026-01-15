<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Procedimiento extends Model
{
    protected $fillable = [
        'nombre',
        'precio',
        'color',
        'estado',
        'comision'
    ];

    /**
     * Calcula el monto de comisión basado en el precio y porcentaje de comisión
     */
    public function calcularComision($cantidad = 1)
    {
        $comisionPorcentaje = $this->comision ?? 0;
        $montoComision = ($this->precio * $comisionPorcentaje / 100) * $cantidad;
        return round($montoComision, 2);
    }
}
