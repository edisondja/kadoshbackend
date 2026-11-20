<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    
   protected $table = 'configs';

    protected $fillable = [
        'nombre', 'descripcion', 'ruta_logo', 'ruta_favicon',
        'email', 'telefono', 'numero_empresa', 'dominio', 'api_whatapps',
        'api_token_ws', 'api_gmail', 'api_token_google',
        'api_instagram', 'token_instagram',
        'nombre_clinica', 'direccion_clinica', 'telefono_clinica',
        'rnc_clinica', 'email_clinica', 'tipo_numero_factura',
        'prefijo_factura', 'usar_google_calendar', 'google_calendar_id',
        'recordatorio_minutos', 'clave_secreta', 'mensaje_cumpleanos',
        'formato_hora_citas', 'tema_apariencia', 'mostrar_firma_documentos',
        'dgii_ecf_activo', 'dgii_ambiente', 'dgii_tipo_ecf', 'dgii_tipo_ingresos',
        'dgii_itbis_modo', 'dgii_secuencia_e32', 'dgii_secuencia_e31',
        'dgii_fecha_vencimiento_secuencia', 'dgii_auto_emitir',
        'dgii_cert_password', 'dgii_cert_loaded',
    ];

    protected $hidden = [
        'dgii_cert_password',
    ];

}
