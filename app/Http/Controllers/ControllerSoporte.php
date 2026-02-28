<?php

namespace App\Http\Controllers;

use App\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ControllerSoporte extends Controller
{
    const EMAIL_SOPORTE_FALLBACK = 'edisondja@gmail.com';

    /**
     * Recibe mensaje del chat de soporte y envía correo al email configurado en Laravel/config.
     */
    public function enviarMensaje(Request $request)
    {
        $request->validate([
            'nombre'  => 'required|string|max:255',
            'email'   => 'required|email',
            'mensaje' => 'required|string|max:5000',
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

            Mail::raw($cuerpo, function ($message) use ($asunto, $emailDestino, $emailUsuario) {
                $message->to($emailDestino)
                    ->replyTo($emailUsuario)
                    ->subject($asunto);
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
