<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'logs';
    
    protected $fillable = [
        'usuario_id',
        'accion',
        'modulo',
        'descripcion',
        'ip_address',
        'user_agent'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Crear un log de auditoría
     */
    public static function crearLog($usuarioId, $modulo, $accion, $descripcion = null, $ipAddress = null, $userAgent = null)
    {
        return self::create([
            'usuario_id' => $usuarioId,
            'modulo' => $modulo,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}
