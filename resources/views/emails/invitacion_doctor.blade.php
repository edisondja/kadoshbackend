<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al sistema</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:20px 24px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;">
                            <h1 style="margin:0;font-size:20px;line-height:1.3;">Cree su acceso al sistema</h1>
                            <p style="margin:6px 0 0 0;font-size:14px;opacity:0.95;">{{ $nombreClinica }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0 0 14px 0;font-size:14px;color:#374151;">
                                Hola <strong>{{ $nombreDoctor }}</strong>,
                            </p>
                            <p style="margin:0 0 18px 0;font-size:14px;color:#374151;line-height:1.5;">
                                La clínica le ha registrado como odontólogo. Use el botón siguiente para elegir su <strong>usuario</strong> y <strong>contraseña</strong> y acceder al sistema.
                                El enlace es de un solo uso y caduca en {{ $diasValidez }} días.
                            </p>
                            <p style="margin:0 0 22px 0;text-align:center;">
                                <a href="{{ $enlaceRegistro }}" style="display:inline-block;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;text-decoration:none;font-weight:600;font-size:15px;padding:14px 28px;border-radius:10px;">
                                    Completar registro
                                </a>
                            </p>
                            <p style="margin:0;font-size:12px;color:#6b7280;word-break:break-all;">
                                Si el botón no funciona, copie y pegue esta dirección en su navegador:<br/>
                                <span style="color:#4f46e5;">{{ $enlaceRegistro }}</span>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
