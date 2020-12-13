<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;



class Paciente extends Model
{
    use LaravelSubQueryTrait;


    public function searchableAs()
    {
        return 'posts_index';
    }

    public function estatus()
    {
        return $this->hasMany('App\Factura','id_paciente');
    }

}
