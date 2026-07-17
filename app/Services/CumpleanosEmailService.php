<?php

namespace App\Services;

use App\Config;
use App\Paciente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CumpleanosEmailService
{
    /**
     * Pacientes que cumplen años hoy y tienen correo válido.
     */
    public function pacientesCumpleanerosHoy()
    {
        $hoy = Carbon::today();

        return Paciente::query()
            ->whereNotNull('fecha_nacimiento')
            ->whereNotNull('correo_electronico')
            ->where('correo_electronico', '!=', '')
            ->whereDay('fecha_nacimiento', $hoy->day)
            ->whereMonth('fecha_nacimiento', $hoy->month)
            ->get()
            ->filter(function ($paciente) {
                return filter_var(trim((string) $paciente->correo_electronico), FILTER_VALIDATE_EMAIL);
            });
    }

    public function construirMensaje(Config $config, Paciente $paciente, $nombreClinica)
    {
        $plantilla = trim((string) ($config->mensaje_cumpleanos ?? ''));
        if ($plantilla === '') {
            $plantilla = '🎉 ¡Hola {nombre}! 🎂 El equipo de {nombreClinica} te desea un feliz cumpleaños 🎈.';
        }

        $nombre = trim((string) $paciente->nombre);
        $clinica = trim((string) $nombreClinica) ?: 'Clínica';

        return str_replace(
            ['{nombre}', '{nombreClinica}'],
            [$nombre, $clinica],
            $plantilla
        );
    }

    public function nombreClinicaDesdeConfig(Config $config = null)
    {
        if (!$config) {
            return 'Clínica';
        }

        $nombre = trim((string) ($config->nombre_clinica ?: $config->nombre));

        return $nombre !== '' ? $nombre : 'Clínica';
    }

    public function datosBrandingCorreo(Config $config = null)
    {
        $email = $config ? trim((string) ($config->email_clinica ?: $config->email)) : '';

        return [
            'logoUrl' => env('MAIL_LOGO_URL', 'https://odontoed.com/edasystems.png'),
            'webUrl' => env('MAIL_WEB_URL', 'https://www.odontoed.com'),
            'supportEmail' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : env('MAIL_FROM_ADDRESS'),
        ];
    }

    /**
     * @return bool true si se envió
     */
    public function enviarCorreoPaciente(Paciente $paciente, Config $config = null, $dryRun = false)
    {
        $config = $config ?: Config::first();
        if (!$config) {
            Log::warning('Cumpleaños: sin registro en configs, no se envía correo.');
            return false;
        }

        $nombreClinica = $this->nombreClinicaDesdeConfig($config);
        $mensaje = $this->construirMensaje($config, $paciente, $nombreClinica);
        $correo = trim((string) $paciente->correo_electronico);
        $nombreCompleto = trim($paciente->nombre . ' ' . $paciente->apellido);
        $asunto = 'Feliz cumpleaños — ' . $nombreClinica;

        if ($dryRun) {
            return true;
        }

        $branding = $this->datosBrandingCorreo($config);
        $replyTo = trim((string) ($config->email_clinica ?: $config->email));

        try {
            Mail::send('emails.cumpleanos_paciente', array_merge($branding, [
                'subjectLine' => $asunto,
                'nombrePaciente' => trim((string) $paciente->nombre),
                'nombreClinica' => $nombreClinica,
                'mensaje' => $mensaje,
            ]), function ($message) use ($correo, $nombreCompleto, $asunto, $replyTo, $nombreClinica) {
                $message->to($correo, $nombreCompleto)->subject($asunto);
                if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $message->replyTo($replyTo, $nombreClinica);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Cumpleaños email falló (paciente ' . $paciente->id . '): ' . $e->getMessage());
            return false;
        }
    }

    /** Evita reenviar el mismo día si el cron corre más de una vez. */
    public function yaEnviadoHoy($databaseName, $pacienteId, Carbon $fecha = null)
    {
        $fecha = $fecha ?: Carbon::today();
        $path = $this->registroPath($databaseName, $fecha);
        if (!is_file($path)) {
            return false;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            return false;
        }

        return in_array((int) $pacienteId, $data, true);
    }

    public function marcarEnviadoHoy($databaseName, $pacienteId, Carbon $fecha = null)
    {
        $fecha = $fecha ?: Carbon::today();
        $path = $this->registroPath($databaseName, $fecha);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ids = [];
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $ids = $decoded;
            }
        }

        $ids[] = (int) $pacienteId;
        $ids = array_values(array_unique($ids));
        file_put_contents($path, json_encode($ids));
    }

    protected function registroPath($databaseName, Carbon $fecha)
    {
        $safeDb = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) $databaseName);

        return storage_path('app/cumpleanos-enviados/' . $safeDb . '/' . $fecha->format('Y-m-d') . '.json');
    }
}
