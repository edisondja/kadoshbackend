<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'precio',
        'categoria',
        'cantidad',
        'stock_minimo',
        'foto_producto',
        'usuario_id',
        'activo'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
