<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{

    public function doctor(){

        return $this->belongsTo(Doctor::class);
    }

    public function recibos(){

        return $this->hasMany(Recibo::class);

    }



}
