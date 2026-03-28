<?php

namespace App\Http\Controllers;

use App\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ControllerSoporte extends Controller
{
    const EMAIL_SOPORTE_FALLBACK = 'edisondja@gmail.com';
    const EMAIL_ASUNTO_CLIENTE = 'Gracias por contactarnos - Soporte Kadosh/OdontoED';

    /**
     * Recibe mensaje del chat de soporte y envía correo al email configurado en Laravel/config.
     */
    public function enviarMensaje(Request $request)
    {
        // Soporta JSON y multipart/form-data (para adjuntos)
        $request->validate([
            'nombre'  => 'required|string|max:255',
            'email'   => 'required|email',
            'mensaje' => 'required|string|max:5000',
            'adjuntos' => 'nullable|array|max:3',
            'adjuntos.*' => 'file|mimes:jpg,jpeg,png,webp,gif|max:5120', // 5MB por imagen
        ], [
            'nombre.required'  => 'El nombre es obligatorio.',
            'email.required'   => 'El correo es obligatorio.',
            'email.email'      => 'El correo no es válido.',
            'mensaje.required' => 'El mensaje es obligatorio.',
        ]);

        try {
            $nombre = $request->input('nombre');
            $emailUsuario = $request->input('email');
            $mensaje = $request->input('mensaje');

            $config = Config::first();
            $emailClinica = ($config && !empty(trim($config->email_clinica ?? '')))
                ? trim($config->email_clinica)
                : null;
            $emailConfig = ($config && !empty(trim($config->email ?? ''))) ? trim($config->email) : null;

            // Correo principal: plataforma (OdontoED). Prioridad: .env → MAIL_FROM_ADDRESS → clínica/config.
            $platformEnv = trim((string) env('SOPORTE_EMAIL_PLATAFORMA', ''));
            $fromMail = (string) config('mail.from.address', '');
            $emailPlataforma = null;
            if ($platformEnv !== '' && filter_var($platformEnv, FILTER_VALIDATE_EMAIL)) {
                $emailPlataforma = $platformEnv;
            } elseif ($fromMail !== '' && filter_var($fromMail, FILTER_VALIDATE_EMAIL)) {
                $emailPlataforma = $fromMail;
            }

            if ($emailPlataforma !== null) {
                $emailDestino = $emailPlataforma;
                // Copia a la clínica si tiene correo distinto al de la plataforma
                $ccClinica = $emailClinica ?: $emailConfig;
                if ($ccClinica !== null && strcasecmp($ccClinica, $emailDestino) === 0) {
                    $ccClinica = null;
                }
            } else {
                $emailDestino = $emailClinica ?: $emailConfig ?: self::EMAIL_SOPORTE_FALLBACK;
                $ccClinica = null;
            }

            $asunto = 'Soporte Kadosh/OdontoED - Mensaje de ' . $nombre;

            $adjuntos = $request->file('adjuntos', []);
            // Compatibilidad si alguna implementación envía un solo archivo como 'adjunto'
            if (empty($adjuntos) && $request->hasFile('adjunto')) {
                $adjuntos = [$request->file('adjunto')];
            }

            $branding = $this->datosBrandingCorreo($config);

            // 1) Enviar al correo de la plataforma (plantilla HTML Edasystems) + CC clínica
            Mail::send('emails.soporte.nuevo_mensaje', array_merge($branding, [
                'subjectLine' => $asunto,
                'nombre' => $nombre,
                'emailUsuario' => $emailUsuario,
                'mensaje' => $mensaje,
            ]), function ($message) use ($asunto, $emailDestino, $ccClinica, $emailUsuario, $adjuntos) {
                $message->to($emailDestino)->replyTo($emailUsuario)->subject($asunto);
                if ($ccClinica !== null) {
                    $message->cc($ccClinica);
                }

                if (is_array($adjuntos)) {
                    foreach ($adjuntos as $file) {
                        if (!$file) {
                            continue;
                        }
                        try {
                            $original = $file->getClientOriginalName() ?: 'adjunto';
                            // Evita nombres raros en el mail
                            $safeName = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'adjunto';
                            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                            $finalName = $safeName . '.' . $ext;

                            $message->attach($file->getRealPath(), [
                                'as' => $finalName,
                                'mime' => $file->getMimeType(),
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('No se pudo adjuntar archivo en soporte: ' . $e->getMessage());
                        }
                    }
                }
            });

            // 2) Confirmación al cliente (misma marca Edasystems / OdontoED)
            try {
                Mail::send('emails.soporte.confirmacion_cliente', array_merge($branding, [
                    'subjectLine' => self::EMAIL_ASUNTO_CLIENTE,
                    'nombre' => $nombre,
                    'mensaje' => $mensaje,
                ]), function ($message) use ($emailUsuario) {
                    $message->to($emailUsuario)->subject(self::EMAIL_ASUNTO_CLIENTE);
                });
            } catch (\Exception $e) {
                Log::error('No se pudo enviar confirmación al cliente (soporte): ' . $e->getMessage());
                throw $e;
            }

            return response()->json([
                'message' => 'Mensaje enviado correctamente. Te responderemos a la brevedad.',
                'cliente_enviado' => true,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje de soporte: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'No se pudo enviar el mensaje. Intente más tarde.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * URL del logo y datos comunes para plantillas de correo (Edasystems / OdontoED).
     */
    private function datosBrandingCorreo(?Config $config): array
    {
        $logo = trim((string) config('mail.logo_url'));
        if ($logo === '') {
            $logo = asset('images/edasystems-logo-email.png');
        }

        $from = config('mail.from.address');
        $supportEmail = is_string($from) && filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : null;

        return [
            'logoUrl' => $logo,
            'webUrl' => 'https://www.odontoed.com',
            'supportEmail' => $supportEmail,
            'subjectLine' => 'OdontoED',
            'nombreClinica' => ($config && !empty(trim((string) ($config->nombre_clinica ?? ''))))
                ? trim($config->nombre_clinica)
                : null,
        ];
    }
}
