<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;


class Recibo extends Model
{
    public function factura(){

         //return $this->BelongsTo('App\Factura');
         return $this->belongsTo('App\Factura','id_factura');

    }

    public function doctorGanancias()
    {
        return $this->hasMany(DoctorGananciaRecibo::class, 'id_recibo');
    }
}
