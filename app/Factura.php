<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;

class Factura extends Model
{

    public function doctor(){

        return $this->belongsTo(Doctor::class);
    }

   
    public function recibos(){

        return $this->hasMany(Recibo::class);

    }

     public function paciente(){

        return $this->BelongsTo('App\Paciente','id_paciente');

    }



}
