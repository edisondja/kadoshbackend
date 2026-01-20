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
        // Si es null, retornar null o array vacío según prefieras
        if ($value === null) {
            return null; // O puedes retornar [] si prefieres siempre un array
        }
        return json_decode($value, true) ?? [];
    }

    /**
     * Establecer medicamentos como JSON
     */
    public function setMedicamentosAttribute($value)
    {
        // Si es null, guardar null directamente
        if ($value === null) {
            $this->attributes['medicamentos'] = null;
        } elseif (is_string($value)) {
            // Si ya es string (JSON), guardarlo directamente
            $this->attributes['medicamentos'] = $value;
        } else {
            // Si es array, convertirlo a JSON
            $this->attributes['medicamentos'] = json_encode($value);
        }
    }
}
