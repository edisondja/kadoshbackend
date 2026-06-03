@extends('emails.layouts.edasystems')

@section('content')
    <h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#0e2b52;line-height:1.3;">
        Hola, {{ $nombre }}
    </h1>
    <p style="margin:0 0 24px;color:#475569;font-size:16px;line-height:1.6;">
        Recibimos tu solicitud de acceso a <strong style="color:#0e2b52;">Odontoed</strong>.
        Nuestro equipo revisará tu consulta y te responderá pronto con información sobre planes, mensualidad e implementación.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f3ff;border-radius:12px;border:1px solid #ddd6fe;">
        <tr>
            <td style="padding:22px 24px;">
                <p style="margin:0 0 10px;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:#6d28d9;font-weight:700;">Motivo de tu consulta</p>
                <p style="margin:0;font-size:15px;color:#1e1b4b;line-height:1.65;">{{ $interesEtiqueta }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:28px 0 0;color:#64748b;font-size:14px;line-height:1.6;">
        Atentamente,<br>
        <strong style="color:#0e2b52;">Equipo Odontoed</strong><br>
        <span style="color:#667eea;font-weight:600;">Edasystems</span>
    </p>
@endsection
