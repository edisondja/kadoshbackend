<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Radiografia extends Model
{
    protected $fillable = ['ruta_radiografia', 'id_usuario', 'comentarios'];

}
