<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subjectLine ?? 'OdontoED' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef2f7;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(14,43,82,0.12);">
                    {{-- Cabecera con logotipo Edasystems --}}
                    <tr>
                        <td style="background-color:#0e2b52;padding:28px 32px;text-align:center;border-bottom:3px solid #667eea;">
                            <a href="{{ $webUrl ?? 'https://www.odontoed.com' }}" target="_blank" rel="noopener" style="text-decoration:none;">
                                <img src="{{ $logoUrl }}" alt="EDA Systems — OdontoED" width="280" height="84" style="display:block;margin:0 auto;height:auto;max-width:280px;width:100%;border:0;outline:none;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 28px;color:#1f2937;font-size:16px;line-height:1.65;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;background-color:#f8fafc;border-top:1px solid #e5e7eb;">
                            <p style="margin:0 0 8px;font-size:13px;color:#64748b;line-height:1.5;text-align:center;">
                                <strong style="color:#0e2b52;">OdontoED</strong> — Sistema de gestión para clínicas dentales
                            </p>
                            <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;text-align:center;">
                                Desarrollado por <strong style="color:#667eea;">Edasystems</strong>
                                @if(!empty($supportEmail))
                                    &nbsp;·&nbsp; <a href="mailto:{{ $supportEmail }}" style="color:#667eea;text-decoration:none;">{{ $supportEmail }}</a>
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:20px 0 0;font-size:11px;color:#94a3b8;text-align:center;max-width:560px;">
                    Este mensaje fue generado automáticamente. Por favor no responda a esta dirección si no corresponde.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
