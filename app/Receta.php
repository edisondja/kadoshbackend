<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    protected $fillable = [
        'id_paciente',
        'id_doctor',
        'medicamentos',
        'indicaciones',
        'diagnostico',
        'fecha',
        'codigo_receta'
    ];

    protected $dates = [
        'fecha',
        'created_at',
        'updated_at'
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    /**
     * Obtener medicamentos como array
     */
    public function getMedicamentosAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    /**
     * Establecer medicamentos como JSON
     */
    public function setMedicamentosAttribute($value)
    {
        $this->attributes['medicamentos'] = is_string($value) ? $value : json_encode($value);
    }
}
