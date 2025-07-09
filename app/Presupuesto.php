<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    
    public function paciente(){

        return $this->belongsTo(Paciente::class,'paciente_id');

    }

}
