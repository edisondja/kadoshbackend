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
            $emailDestino = ($config && !empty(trim($config->email_clinica ?? '')))
                ? trim($config->email_clinica)
                : (($config && !empty(trim($config->email ?? ''))) ? trim($config->email) : self::EMAIL_SOPORTE_FALLBACK);

            $asunto = 'Soporte Kadosh/OdontoED - Mensaje de ' . $nombre;
            $cuerpo = "Nombre: {$nombre}\n";
            $cuerpo .= "Correo del usuario: {$emailUsuario}\n\n";
            $cuerpo .= "Mensaje:\n{$mensaje}\n";

            $adjuntos = $request->file('adjuntos', []);
            // Compatibilidad si alguna implementación envía un solo archivo como 'adjunto'
            if (empty($adjuntos) && $request->hasFile('adjunto')) {
                $adjuntos = [$request->file('adjunto')];
            }

            Mail::raw($cuerpo, function ($message) use ($asunto, $emailDestino, $emailUsuario, $adjuntos) {
                $message->to($emailDestino)
                    ->replyTo($emailUsuario)
                    ->subject($asunto);

                if (is_array($adjuntos)) {
                    foreach ($adjuntos as $file) {
                        if (!$file) continue;
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

            return response()->json([
                'message' => 'Mensaje enviado correctamente. Te responderemos a la brevedad.',
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
}
