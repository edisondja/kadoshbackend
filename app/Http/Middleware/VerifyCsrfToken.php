<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *.
     * @var array
     */
    protected $except = [
        '/api/login',
        '/api/crear_factura',
        '/api/editando_factura/',
        // Chat de soporte: petición multipart desde SPA sin cookie CSRF del mismo dominio
        '/api/soporte/enviar',
        '/api/invitacion_paciente/registrar',
        '/api/invitacion_paciente/crear',
        '/api/invitacion_doctor/registrar',
    ];
}
