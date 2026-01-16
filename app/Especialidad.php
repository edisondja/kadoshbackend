<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    protected $table = 'especialidades';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean'
    ];

    /**
     * Obtener doctores con esta especialidad
     */
    public function doctores()
    {
        return $this->hasMany(Doctor::class, 'especialidad', 'nombre');
    }
}
