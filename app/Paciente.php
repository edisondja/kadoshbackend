<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Paciente extends Model
{


    public function searchableAs()
    {
        return 'posts_index';
    }

    public function estatus()
    {
        return $this->hasMany('App\Factura','id_paciente');
    }

}
