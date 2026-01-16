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
        'api_instagram', 'token_instagram',
        'nombre_clinica', 'direccion_clinica', 'telefono_clinica',
        'rnc_clinica', 'email_clinica', 'tipo_numero_factura',
        'prefijo_factura', 'usar_google_calendar', 'google_calendar_id',
        'recordatorio_minutos', 'clave_secreta'
    ];

}
