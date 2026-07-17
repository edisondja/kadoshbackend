#!/usr/bin/env php
<?php
/**
 * Envío de prueba del correo de cumpleaños (sin artisan).
 * Uso: php scripts/enviar_cumpleanos_prueba.php edisondja@gmail.com
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$destino = isset($argv[1]) ? trim($argv[1]) : '';
if ($destino === '' || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/enviar_cumpleanos_prueba.php correo@ejemplo.com\n");
    exit(1);
}

$env = [];
$envPath = $root . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($k, $v) = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\"'");
    }
}

$host = $env['MAIL_HOST'] ?? 'localhost';
$port = (int) ($env['MAIL_PORT'] ?? 587);
$user = $env['MAIL_USERNAME'] ?? '';
$pass = $env['MAIL_PASSWORD'] ?? '';
$encryption = $env['MAIL_ENCRYPTION'] ?? null;
$from = $env['MAIL_FROM_ADDRESS'] ?? $user;
$fromName = $env['MAIL_FROM_NAME'] ?? 'OdontoED';

$nombrePaciente = 'Edison';
$nombreClinica = 'Odontoed Demo';
$mensaje = "🎉 ¡Hola {$nombrePaciente}! 🎂 El equipo de {$nombreClinica} te desea un feliz cumpleaños 🎈.";
$logoUrl = $env['MAIL_LOGO_URL'] ?? 'https://odontoed.com/edasystems.png';

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:32px 16px;">
<tr><td align="center">
<table width="100%" style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden;">
<tr><td style="background:#0e2b52;padding:28px;text-align:center;">
<img src="{$logoUrl}" alt="EDA Systems" width="240" style="max-width:240px;height:auto;">
</td></tr>
<tr><td style="padding:36px 32px;color:#1f2937;">
<h1 style="margin:0 0 16px;font-size:24px;color:#0e2b52;">🎂 ¡Feliz cumpleaños, {$nombrePaciente}!</h1>
<p style="margin:0 0 24px;font-size:16px;line-height:1.7;">{$mensaje}</p>
<p style="margin:0;color:#64748b;">Con cariño,<br><strong>{$nombreClinica}</strong></p>
<p style="margin:24px 0 0;font-size:12px;color:#94a3b8;">Correo de prueba — job cumpleanos:enviar</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

try {
    $transport = (new Swift_SmtpTransport($host, $port, $encryption ?: null))
        ->setUsername($user)
        ->setPassword($pass);

    $mailer = new Swift_Mailer($transport);
    $message = (new Swift_Message('Feliz cumpleaños — ' . $nombreClinica . ' (prueba)'))
        ->setFrom([$from => $fromName])
        ->setTo([$destino => $nombrePaciente . ' De Jesus'])
        ->setBody($html, 'text/html');

    $sent = $mailer->send($message);
    if ($sent) {
        echo "OK — correo de prueba enviado a {$destino}\n";
        exit(0);
    }
    echo "ERROR — el servidor SMTP no confirmó el envío\n";
    exit(1);
} catch (Exception $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
