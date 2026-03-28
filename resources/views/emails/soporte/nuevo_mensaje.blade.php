@extends('emails.layouts.edasystems')

@section('content')
    <h1 style="margin:0 0 20px;font-size:22px;font-weight:700;color:#0e2b52;line-height:1.3;">
        Nuevo mensaje de soporte
    </h1>
    <p style="margin:0 0 24px;color:#64748b;font-size:15px;">
        Un usuario ha enviado un mensaje desde el chat de soporte de la plataforma.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f1f5f9;border-radius:12px;border-left:4px solid #667eea;">
        <tr>
            <td style="padding:20px 22px;">
                <p style="margin:0 0 12px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Nombre</p>
                <p style="margin:0 0 18px;font-size:17px;font-weight:600;color:#0f172a;">{{ $nombre }}</p>

                <p style="margin:0 0 12px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Correo (responder a)</p>
                <p style="margin:0 0 18px;font-size:16px;">
                    <a href="mailto:{{ $emailUsuario }}" style="color:#667eea;text-decoration:none;font-weight:600;">{{ $emailUsuario }}</a>
                </p>

                @if(!empty($nombreClinica))
                    <p style="margin:0 0 12px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Clínica / instancia</p>
                    <p style="margin:0 0 18px;font-size:15px;color:#334155;">{{ $nombreClinica }}</p>
                @endif

                <p style="margin:0 0 12px;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Mensaje</p>
                <div style="margin:0;font-size:15px;color:#334155;line-height:1.6;white-space:pre-wrap;">{{ $mensaje }}</div>
            </td>
        </tr>
    </table>
@endsection
