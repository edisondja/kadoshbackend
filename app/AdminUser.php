<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AdminUser extends Model
{
    protected $connection = 'mysql';

    protected $table = 'admin_users';

    protected $fillable = [
        'usuario',
        'password',
        'nombre',
        'apellido',
        'activo',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function verificarClave($clave)
    {
        return \Illuminate\Support\Facades\Hash::check($clave, $this->password);
    }
}
