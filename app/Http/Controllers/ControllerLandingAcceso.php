<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ControllerLandingAcceso extends Controller
{
    const EMAIL_DESTINO_FALLBACK = 'serive@odontoed.com';
    const EMAIL_CC_DEFAULT = 'edisondja@gmail.com';
    const EMAIL_ASUNTO_CLIENTE = 'Recibimos tu solicitud de acceso — Odontoed';

    /**
     * Genera un captcha matemático de un solo uso (anti-bots).
     */
    public function generarCaptcha()
    {
        $a = random_int(2, 12);
        $b = random_int(2, 12);
        $token = Str::random(40);

        Cache::put('landing_captcha_' . $token, (string) ($a + $b), 10);

        return response()->json([
            'token' => $token,
            'pregunta' => "¿Cuánto es {$a} + {$b}?",
        ]);
    }

    /**
     * Recibe solicitud de acceso desde la landing y envía correos.
     */
    public function solicitarAcceso(Request $request)
    {
        if ($request->filled('website_url')) {
            return response()->json([
                'message' => 'Solicitud recibida. Te contactaremos pronto.',
            ], 200);
        }

        $request->validate([
            'nombre' => 'required|string|max:120|regex:/^[\pL\s\.\-\']+$/u',
            'email' => 'required|email|max:180',
            'telefono' => 'required|string|max:30|regex:/^[0-9+\-\s()]{7,30}$/',
            'nombre_clinica' => 'nullable|string|max:180',
            'ciudad' => 'nullable|string|max:120',
            'interes' => 'required|in:demo,informacion,mensualidad,implementacion',
            'consulta_mensualidad' => 'nullable|string|max:2000',
            'mensaje' => 'nullable|string|max:3000',
            'captcha_token' => 'required|string|size:40',
            'captcha_respuesta' => 'required|digits_between:1,3',
        ], [
            'nombre.required' => 'Indique su nombre.',
            'nombre.regex' => 'El nombre contiene caracteres no permitidos.',
            'email.required' => 'Indique su correo electrónico.',
            'email.email' => 'El correo no es válido.',
            'telefono.required' => 'Indique su número de teléfono.',
            'telefono.regex' => 'El teléfono no es válido.',
            'interes.required' => 'Seleccione el motivo de su consulta.',
            'interes.in' => 'Seleccione un motivo de consulta válido.',
            'captcha_token.required' => 'Complete la verificación anti-bots.',
            'captcha_respuesta.required' => 'Responda la pregunta de verificación.',
            'captcha_respuesta.digits_between' => 'La respuesta del captcha debe ser un número.',
        ]);

        $cacheKey = 'landing_captcha_' . $request->input('captcha_token');
        $respuestaEsperada = Cache::pull($cacheKey);

        if ($respuestaEsperada === null || (string) $request->input('captcha_respuesta') !== $respuestaEsperada) {
            return response()->json([
                'message' => 'La verificación anti-bots es incorrecta o expiró. Intente de nuevo.',
                'errors' => ['captcha_respuesta' => ['Respuesta incorrecta o expirada.']],
            ], 422);
        }

        $datos = [
            'nombre' => $this->sanitizarTexto($request->input('nombre'), 120),
            'email' => filter_var(trim($request->input('email')), FILTER_SANITIZE_EMAIL),
            'telefono' => $this->sanitizarTexto($request->input('telefono'), 30),
            'nombre_clinica' => $this->sanitizarTexto($request->input('nombre_clinica', ''), 180),
            'ciudad' => $this->sanitizarTexto($request->input('ciudad', ''), 120),
            'interes' => $request->input('interes'),
            'consulta_mensualidad' => $this->sanitizarTexto($request->input('consulta_mensualidad', ''), 2000),
            'mensaje' => $this->sanitizarTexto($request->input('mensaje', ''), 3000),
            'ip' => $request->ip(),
            'user_agent' => Str::limit($this->sanitizarTexto($request->userAgent() ?: '', 255), 255, ''),
        ];

        $datos['interes_etiqueta'] = $this->etiquetaInteres($datos['interes']);
        $emailDestino = $this->emailDestinoPlataforma();

        $persistido = $this->persistirSolicitud($datos);
        $correoAdminOk = $this->enviarCorreoAdmin($datos, $emailDestino);
        $this->enviarCorreoConfirmacion($datos);

        if ($correoAdminOk || $persistido) {
            return response()->json([
                'message' => '¡Gracias! Recibimos tu solicitud. Te contactaremos pronto al correo y teléfono indicados.',
                'correo_enviado' => $correoAdminOk,
            ], 200);
        }

        return response()->json([
            'message' => 'No se pudo enviar la solicitud. Intente más tarde o escríbanos a serive@odontoed.com.',
        ], 500);
    }

    private function enviarCorreoAdmin(array $datos, string $emailDestino): bool
    {
        $branding = $this->datosBrandingCorreo($emailDestino);
        $asuntoAdmin = 'Solicitud de acceso Odontoed — ' . $datos['nombre'];
        $emailsCopia = $this->emailsCopia($emailDestino);

        try {
            Mail::send('emails.landing.solicitud_acceso_admin', array_merge($branding, [
                'subjectLine' => $asuntoAdmin,
                'nombre' => $datos['nombre'],
                'emailUsuario' => $datos['email'],
                'telefono' => $datos['telefono'],
                'nombreClinica' => $datos['nombre_clinica'] ?: '—',
                'ciudad' => $datos['ciudad'] ?: '—',
                'interesEtiqueta' => $datos['interes_etiqueta'],
                'consultaMensualidad' => $datos['consulta_mensualidad'] ?: '—',
                'mensaje' => $datos['mensaje'] ?: '—',
                'ip' => $datos['ip'],
                'userAgent' => $datos['user_agent'],
            ]), function ($message) use ($asuntoAdmin, $emailDestino, $datos, $emailsCopia) {
                $message->to($emailDestino)
                    ->replyTo($datos['email'], $datos['nombre'])
                    ->subject($asuntoAdmin);

                foreach ($emailsCopia as $copia) {
                    $message->cc($copia);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Error al enviar solicitud de acceso landing (admin): ' . $e->getMessage());
            return false;
        }
    }

    private function enviarCorreoConfirmacion(array $datos): void
    {
        $branding = $this->datosBrandingCorreo($this->emailDestinoPlataforma());

        try {
            Mail::send('emails.landing.solicitud_acceso_confirmacion', array_merge($branding, [
                'subjectLine' => self::EMAIL_ASUNTO_CLIENTE,
                'nombre' => $datos['nombre'],
                'interesEtiqueta' => $datos['interes_etiqueta'],
            ]), function ($message) use ($datos) {
                $message->to($datos['email'])->subject(self::EMAIL_ASUNTO_CLIENTE);
            });
        } catch (\Exception $e) {
            Log::warning('No se pudo enviar confirmación al cliente (landing acceso): ' . $e->getMessage());
        }
    }

    /**
     * Respaldo en disco por si SMTP falla (revisar storage/app/solicitudes-acceso/).
     */
    private function persistirSolicitud(array $datos): bool
    {
        try {
            $dir = storage_path('app/solicitudes-acceso');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $registro = array_merge($datos, [
                'created_at' => date('c'),
            ]);

            $archivo = $dir . '/' . date('Y-m-d') . '.jsonl';
            $linea = json_encode($registro, JSON_UNESCAPED_UNICODE) . "\n";

            return file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX) !== false;
        } catch (\Exception $e) {
            Log::error('No se pudo guardar solicitud de acceso en disco: ' . $e->getMessage());
            return false;
        }
    }

    private function datosBrandingCorreo(string $supportEmail): array
    {
        $logo = trim((string) config('mail.logo_url'));
        if ($logo === '') {
            $logo = asset('images/edasystems-logo-email.png');
        }

        return [
            'logoUrl' => $logo,
            'webUrl' => 'https://www.odontoed.com',
            'supportEmail' => $supportEmail,
            'subjectLine' => 'Odontoed',
        ];
    }

    private function emailDestinoPlataforma(): string
    {
        $env = trim((string) env('LANDING_ACCESO_EMAIL', ''));
        if ($env !== '' && filter_var($env, FILTER_VALIDATE_EMAIL)) {
            return $env;
        }

        $soporte = trim((string) env('SOPORTE_EMAIL_PLATAFORMA', ''));
        if ($soporte !== '' && filter_var($soporte, FILTER_VALIDATE_EMAIL)) {
            return $soporte;
        }

        $from = (string) config('mail.from.address', '');
        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }

        return self::EMAIL_DESTINO_FALLBACK;
    }

    /**
     * Correos en copia (CC) para cada solicitud de acceso.
     */
    private function emailsCopia(string $emailDestino): array
    {
        $raw = trim((string) env('LANDING_ACCESO_CC', self::EMAIL_CC_DEFAULT));
        if ($raw === '') {
            return [];
        }

        $lista = preg_split('/[,;]+/', $raw);
        $emails = [];

        foreach ($lista as $item) {
            $email = trim($item);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            if (strcasecmp($email, $emailDestino) === 0) {
                continue;
            }
            $emails[] = $email;
        }

        return array_values(array_unique($emails));
    }

    private function etiquetaInteres(string $interes): string
    {
        $map = [
            'demo' => 'Quiero una demostración del sistema',
            'informacion' => 'Información general sobre Odontoed',
            'mensualidad' => 'Consulta sobre mensualidad y planes',
            'implementacion' => 'Implementación / migración de mi clínica',
        ];

        return $map[$interes] ?? $interes;
    }

    private function sanitizarTexto(?string $valor, int $maxLen): string
    {
        $valor = strip_tags((string) $valor);
        $valor = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valor);
        $valor = preg_replace('/[<>{}\[\]`\\\\]/u', '', $valor);
        $valor = trim(preg_replace('/\s+/u', ' ', $valor));

        if (function_exists('mb_substr')) {
            return mb_substr($valor, 0, $maxLen);
        }

        return substr($valor, 0, $maxLen);
    }
}
