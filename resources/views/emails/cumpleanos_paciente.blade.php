@extends('emails.layouts.edasystems')

@section('content')
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#0e2b52;line-height:1.3;">
        🎂 ¡Feliz cumpleaños, {{ $nombrePaciente }}!
    </h1>
    <p style="margin:0 0 24px;color:#475569;font-size:16px;line-height:1.7;white-space:pre-line;">
        {{ $mensaje }}
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fef3c7;border-radius:12px;border:1px solid #fcd34d;">
        <tr>
            <td style="padding:20px 24px;text-align:center;">
                <p style="margin:0;font-size:15px;color:#92400e;line-height:1.6;">
                    Con cariño,<br>
                    <strong style="color:#0e2b52;">{{ $nombreClinica }}</strong>
                </p>
            </td>
        </tr>
    </table>
@endsection
