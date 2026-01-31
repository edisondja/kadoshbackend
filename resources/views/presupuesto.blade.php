<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $asunto }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f6f6;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo img {
            max-width: 140px;
            margin-bottom: 10px;
        }

        .company-info {
            font-size: 14px;
            color: #555;
            line-height: 18px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin: 25px 0 15px;
            color: #333;
        }

        .content {
            font-size: 16px;
            color: #444;
            margin-bottom: 25px;
            line-height: 24px;
        }

        .button {
            display: inline-block;
            background: #3b82f6;
            color: white !important;
            padding: 12px 22px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            margin-top: 10px;
        }

        .footer {
            font-size: 13px;
            color: #777;
            margin-top: 25px;
            text-align: center;
            line-height: 18px;
        }
    </style>
</head>

<body>
<div class="container">

    {{-- Encabezado --}}
    <div class="header">
        <div class="logo">
            @if($logo_compania)
                <img src="{{ $logo_compania }}" alt="Logo">
            @endif
        </div>
        <div class="company-info">
            <div><strong>{{ $nombre_compania }}</strong></div>
            <div>{{ $direccion_compania }}</div>
            <div>Tel: {{ $telefono_compania }}</div>
        </div>
    </div>

    {{-- Título --}}
    <div class="title">
        {{ $asunto }}
    </div>

    {{-- Contenido --}}
    <div class="content">
        <p>Estimado(a),</p>

        <p>
            Le enviamos adjunto el presupuesto solicitado.
        </p>

        <p style="font-style: italic; color: #666;">
            Presupuesto sujeto a cambios.
        </p>

        <p>
            Si necesita alguna modificación o desea proceder, puede contactarnos en cualquier momento.
        </p>
    </div>

    {{-- Botón opcional (puedes eliminarlo si no lo necesitas) --}}
    <div style="text-align: center;">
        <a href="#" class="button">Visitar nuestro sitio</a>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Este correo fue generado automáticamente.<br>
        {{ $nombre_compania }} — {{ $telefono_compania }}
    </div>

</div>
</body>
</html>
