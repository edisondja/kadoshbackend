<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;

class Cita extends Model
{
    

    protected $fillable = [
    'motivo',
    'inicio',
    'fin',
    'paciente_id',
    'doctor_id',
    'google_event_id',
    ];

    public function doctor(){

        return $this->belongsTo(Doctor::Class);
    }    
         
    public function paciente(){

        return $this->belongsTo(Paciente::class);
    
    }

}
