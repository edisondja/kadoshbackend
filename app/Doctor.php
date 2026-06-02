<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'numero_telefono',
        'correo_electronico',
        'id_usuario',
        'especialidad',
        'sexo',
        'estado',
        'porcentaje_ingresos'
    ];

    /**
     * Asigna ganancia automática al doctor según porcentaje_ingresos sobre el monto del recibo.
     */
    public function aplicarGananciaAutomaticaRecibo($recibo)
    {
        $porcentaje = floatval($this->porcentaje_ingresos ?? 0);
        if ($porcentaje <= 0 || !$recibo || !$recibo->id) {
            return null;
        }

        $existente = DoctorGananciaRecibo::where('id_recibo', $recibo->id)
            ->where('id_doctor', $this->id)
            ->first();
        if ($existente) {
            return $existente;
        }

        $monto = floatval($recibo->monto);
        $gananciaDoctor = round($monto * $porcentaje / 100, 2);
        $gananciaClinica = round(max(0, $monto - $gananciaDoctor), 2);

        return DoctorGananciaRecibo::create([
            'id_recibo' => $recibo->id,
            'id_doctor' => $this->id,
            'ganancia_doctor' => $gananciaDoctor,
            'ganancia_clinica' => $gananciaClinica,
            'observaciones' => 'Automático: ' . number_format($porcentaje, 2) . '% del pago',
        ]);
    }

    public function cita(){

        return $this->hasMany(Cita::class);
        
    }

    public function salarios()
    {
        return $this->hasMany(SalarioDoctor::class);
    }

}
