@extends('emails.layouts.edasystems')

@section('content')
    <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#0e2b52;line-height:1.3;">
        Falla del sistema
    </h1>
    <p style="margin:0 0 24px;color:#64748b;font-size:15px;">
        Se detectó una excepción en producción. Detalle técnico abajo.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fef2f2;border-radius:12px;border-left:4px solid #dc2626;margin-bottom:20px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0 0 8px;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;color:#991b1b;font-weight:700;">Excepción</p>
                <p style="margin:0 0 14px;font-size:15px;font-weight:600;color:#7f1d1d;word-break:break-word;">{{ $errorClass }}</p>
                <p style="margin:0;font-size:14px;color:#450a0a;line-height:1.5;white-space:pre-wrap;word-break:break-word;">{{ $errorMessage }}</p>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f1f5f9;border-radius:12px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Cuándo</p>
                <p style="margin:0 0 14px;font-size:14px;color:#0f172a;">{{ $occurredAt }}</p>

                @if(!empty($requestHost))
                    <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Host / tenant</p>
                    <p style="margin:0 0 14px;font-size:14px;color:#0f172a;">{{ $requestHost }}@if(!empty($tenantDb)) · {{ $tenantDb }}@endif</p>
                @endif

                @if(!empty($requestPath))
                    <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Request</p>
                    <p style="margin:0 0 14px;font-size:14px;color:#0f172a;">{{ $requestMethod }} {{ $requestPath }}</p>
                @endif

                <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Archivo</p>
                <p style="margin:0 0 14px;font-size:13px;color:#334155;word-break:break-all;">{{ $errorFile }}:{{ $errorLine }}</p>

                <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:600;">Stack (resumen)</p>
                <pre style="margin:0;font-size:11px;line-height:1.45;color:#475569;white-space:pre-wrap;word-break:break-word;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">{{ $traceText }}</pre>
            </td>
        </tr>
    </table>
@endsection
