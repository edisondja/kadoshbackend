@extends('emails.layouts.edasystems')

@section('content')
    <h1 style="margin:0 0 20px;font-size:22px;font-weight:700;color:#0e2b52;line-height:1.3;">
        Nueva solicitud de acceso
    </h1>
    <p style="margin:0 0 24px;color:#64748b;font-size:15px;">
        Un visitante de la landing page solicita información o acceso al sistema Odontoed.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f1f5f9;border-radius:12px;border-left:4px solid #667eea;">
        <tr>
            <td style="padding:20px 22px;">
                <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Nombre</p>
                <p style="margin:0 0 16px;font-size:17px;font-weight:600;color:#0f172a;">{{ $nombre }}</p>

                <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Correo</p>
                <p style="margin:0 0 16px;font-size:16px;">
                    <a href="mailto:{{ $emailUsuario }}" style="color:#667eea;text-decoration:none;font-weight:600;">{{ $emailUsuario }}</a>
                </p>

                <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Teléfono</p>
                <p style="margin:0 0 16px;font-size:16px;color:#334155;">{{ $telefono }}</p>

                <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Clínica</p>
                <p style="margin:0 0 16px;font-size:15px;color:#334155;">{{ $nombreClinica }}</p>

                <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Ciudad</p>
                <p style="margin:0 0 16px;font-size:15px;color:#334155;">{{ $ciudad }}</p>

                <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Motivo</p>
                <p style="margin:0 0 16px;font-size:15px;color:#334155;font-weight:600;">{{ $interesEtiqueta }}</p>

                <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Consulta sobre mensualidad / planes</p>
                <div style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.6;white-space:pre-wrap;">{{ $consultaMensualidad }}</div>

                <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Mensaje adicional</p>
                <div style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.6;white-space:pre-wrap;">{{ $mensaje }}</div>

                <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">IP: {{ $ip }} · {{ $userAgent }}</p>
            </td>
        </tr>
    </table>
@endsection
