<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{

    public function doctor(){

        return $this->BelongsTo('App\Doctor','id_doctor');
    }

   
    public function recibos(){

        return $this->hasMany(Recibo::class);

    }

     public function paciente(){

        return $this->BelongsTo('App\Paciente','id_paciente');

    }



}
