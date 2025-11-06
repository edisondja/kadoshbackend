<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago</title>
</head>
<body>
    <img src="{{ $logo_compania }}" alt="Logo de {{ $nombre_compania }}" style="width:150px;">
    <h2>Estimado paciente,</h2>
    <p>Adjunto encontrará el recibo de pago correspondiente a su procedimiento en <strong>{{ $nombre_compania }}</strong>.</p>
    <p>Gracias por confiar en nosotros.</p>
    <br>
    <p>Atentamente,</p>
    <p><strong>{{ $nombre_compania }} {{ $direccion_compania }}</strong></p>
    <p><strong>Teléfono: {{ $telefono_compania }}</strong></p>
</body>
</html>
