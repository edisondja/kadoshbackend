<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    
   protected $table = 'configs';

    protected $fillable = [
        'nombre', 'descripcion', 'ruta_logo', 'ruta_favicon',
        'email', 'telefono', 'dominio', 'api_whatapps',
        'api_token_ws', 'api_gmail', 'api_token_google',
        'api_instagram', 'token_instagram'
    ];

}
