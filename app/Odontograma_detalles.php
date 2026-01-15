<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Odontograma_detalles extends Model
{
    protected $table = 'odontograma_detalles';
    
    protected $fillable = [
        'odontograma_id',
        'diente',
        'cara',
        'tipo',
        'descripcion',
        'precio',
        'color',
    ];

    public function odontograma()
    {
        return $this->belongsTo(Odontograma::class);
    }
}
