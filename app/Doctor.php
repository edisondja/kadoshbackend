<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{

    public function cita(){

        return $this->hasMany(Cita::class);
        
    }

}
