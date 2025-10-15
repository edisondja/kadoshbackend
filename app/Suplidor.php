<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Suplidor extends Model
{

    protected $fillable = [
        'nombre', 'descripcion', 'rnc_suplidor', 'usuario_id'
    ];
}


