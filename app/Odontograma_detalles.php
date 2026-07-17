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
        'card_pos_x_pct',
        'card_pos_y_pct',
    ];

    public function odontograma()
    {
        return $this->belongsTo(Odontograma::class);
    }
}
