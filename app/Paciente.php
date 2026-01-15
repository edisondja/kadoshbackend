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

    public function odontograma()
    {
        return $this->hasOne('App\Models\Odontograma','paciente_id');
    }

    public function estatus()
    {
        return $this->hasMany('App\Factura','id_paciente');
    }

    public function doctor(){

        return $this->belongsTo('App\Doctor','id_doctor');

    }

    public function recetas()
    {
        return $this->hasMany(Receta::class, 'id_paciente');
    }


}
