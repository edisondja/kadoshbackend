<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalarioDoctor extends Model
{
    protected $table = 'salarios_doctores';
    
    protected $fillable = [
        'doctor_id',
        'salario',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'comentarios'
    ];

    protected $dates = [
        'fecha_inicio',
        'fecha_fin',
        'created_at',
        'updated_at'
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
