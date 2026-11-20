<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envía alertas de fallos del sistema a edisondja@gmail.com
 * usando el SMTP corporativo (MAIL_FROM_ADDRESS / service@odontoed.com).
 */
class SystemErrorMailer
{
    const DEFAULT_TO = 'edisondja@gmail.com';

    /** Segundos entre correos del mismo error (huella). */
    const THROTTLE_SECONDS = 900; // 15 min

    public static function notify(Throwable $exception)
    {
        try {
            if (!self::shouldNotify($exception)) {
                return;
            }

            $fingerprint = self::fingerprint($exception);
            $cacheKey = 'system_error_mail:' . $fingerprint;
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, 1, self::THROTTLE_SECONDS);

            $to = trim((string) config('mail.system_error_to', env('SYSTEM_ERROR_EMAIL', self::DEFAULT_TO)));
            if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $to = self::DEFAULT_TO;
            }

            $host = '';
            $path = '';
            $method = '';
            $tenant = '';
            try {
                if (function_exists('request') && request()) {
                    $req = request();
                    $host = (string) $req->getHost();
                    $path = (string) $req->getRequestUri();
                    $method = (string) $req->method();
                }
            } catch (\Exception $e) {
                // ignore
            }

            try {
                $tenant = (string) (config('database.connections.mysql.database') ?: '');
            } catch (\Exception $e) {
                $tenant = '';
            }

            $class = get_class($exception);
            $message = (string) $exception->getMessage();
            $file = (string) $exception->getFile();
            $line = (int) $exception->getLine();
            $trace = self::shortTrace($exception);

            $asunto = '[OdontoED] Falla del sistema — ' . class_basename($class);
            if ($host !== '') {
                $asunto .= ' · ' . $host;
            }

            $logoUrl = trim((string) config('mail.logo_url', ''));
            if ($logoUrl === '') {
                $logoUrl = 'https://www.odontoed.com/edasystems.png';
            }

            Mail::send('emails.sistema.falla', [
                'subjectLine' => $asunto,
                'logoUrl' => $logoUrl,
                'webUrl' => 'https://www.odontoed.com',
                'supportEmail' => config('mail.from.address', 'service@odontoed.com'),
                'errorClass' => $class,
                'errorMessage' => $message,
                'errorFile' => $file,
                'errorLine' => $line,
                'requestHost' => $host,
                'requestPath' => $path,
                'requestMethod' => $method,
                'tenantDb' => $tenant,
                'occurredAt' => date('Y-m-d H:i:s'),
                'traceText' => $trace,
            ], function ($mail) use ($to, $asunto) {
                $mail->to($to)->subject($asunto);
            });
        } catch (Throwable $mailError) {
            // Nunca romper el flujo de report() por un fallo de correo
            try {
                Log::warning('SystemErrorMailer: no se pudo enviar alerta: ' . $mailError->getMessage());
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    protected static function shouldNotify(Throwable $exception)
    {
        // No alertar validaciones / auth / 4xx habituales
        $skip = [
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Illuminate\Session\TokenMismatchException::class,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class,
        ];

        foreach ($skip as $class) {
            if ($exception instanceof $class) {
                return false;
            }
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            $status = (int) $exception->getStatusCode();
            if ($status > 0 && $status < 500) {
                return false;
            }
        }

        // Evitar bucles si falla el propio mailer / transporte
        $msg = strtolower((string) $exception->getMessage());
        if (strpos($msg, 'systemerrormailer') !== false) {
            return false;
        }

        return true;
    }

    protected static function fingerprint(Throwable $exception)
    {
        return sha1(
            get_class($exception) . '|' .
            substr((string) $exception->getMessage(), 0, 180) . '|' .
            $exception->getFile() . '|' .
            $exception->getLine()
        );
    }

    protected static function shortTrace(Throwable $exception)
    {
        $lines = explode("\n", (string) $exception->getTraceAsString());
        $lines = array_slice($lines, 0, 25);
        return implode("\n", $lines);
    }
}
