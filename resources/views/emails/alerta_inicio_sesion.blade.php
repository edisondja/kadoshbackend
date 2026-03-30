<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de inicio de sesion</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:20px 24px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;">
                            <h1 style="margin:0;font-size:20px;line-height:1.3;">Alerta de inicio de sesion</h1>
                            <p style="margin:6px 0 0 0;font-size:14px;opacity:0.95;">{{ $nombreClinica }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0 0 14px 0;font-size:14px;color:#374151;">
                                Se detecto un nuevo inicio de sesion en el sistema.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                                <tr>
                                    <td style="padding:10px;border:1px solid #e5e7eb;background:#f9fafb;width:170px;"><strong>Usuario</strong></td>
                                    <td style="padding:10px;border:1px solid #e5e7eb;">{{ $nombreUsuario }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px;border:1px solid #e5e7eb;background:#f9fafb;"><strong>Rol</strong></td>
                                    <td style="padding:10px;border:1px solid #e5e7eb;">{{ $rol }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px;border:1px solid #e5e7eb;background:#f9fafb;"><strong>Fecha y hora</strong></td>
                                    <td style="padding:10px;border:1px solid #e5e7eb;">{{ $fechaHora }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px;border:1px solid #e5e7eb;background:#f9fafb;"><strong>IP</strong></td>
                                    <td style="padding:10px;border:1px solid #e5e7eb;">{{ $ip }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px;border:1px solid #e5e7eb;background:#f9fafb;"><strong>Ubicacion aproximada</strong></td>
                                    <td style="padding:10px;border:1px solid #e5e7eb;">{{ $ubicacion }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px;border:1px solid #e5e7eb;background:#f9fafb;"><strong>Navegador</strong></td>
                                    <td style="padding:10px;border:1px solid #e5e7eb;word-break:break-word;">{{ $navegador }}</td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0 0;font-size:12px;color:#6b7280;">
                                Si no reconoce este acceso, cambie la clave del usuario y revise la seguridad de su cuenta.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
