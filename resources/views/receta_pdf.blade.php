<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receta Médica - {{ $receta->codigo_receta }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header img {
            display: block;
            margin: 0 auto 15px auto;
        }
        .header h1 {
            margin: 10px 0;
            font-size: 26px;
            color: #2c3e50;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0;
            color: #555;
            font-size: 11px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .info-value {
            flex: 1;
        }
        .medicamentos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .medicamentos-table th,
        .medicamentos-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .medicamentos-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .diagnostico, .indicaciones {
            margin: 20px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #333;
        }
        .diagnostico h3, .indicaciones h3 {
            margin-top: 0;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 10px;
            color: #666;
        }
        .firma {
            margin-top: 40px;
            text-align: right;
        }
        .firma-line {
            border-top: 1px solid #333;
            width: 300px;
            margin-left: auto;
            margin-top: 60px;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">
        @elseif($config && $config->ruta_logo)
            <img src="{{ public_path($config->ruta_logo) }}" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">
        @endif
        <h1 style="margin-top: 10px; margin-bottom: 10px;">{{ $config->nombre_clinica ?? ($config->nombre ?? 'CLÍNICA DENTAL') }}</h1>
        @if($config && ($config->direccion_clinica || $config->telefono_clinica || $config->email_clinica))
            <p style="margin: 5px 0;">
                @if($config->direccion_clinica)
                    {{ $config->direccion_clinica }}
                @endif
            </p>
            <p style="margin: 5px 0;">
                @if($config->telefono_clinica)
                    Tel: {{ $config->telefono_clinica }}
                @endif
                @if($config->telefono_clinica && $config->email_clinica)
                    | 
                @endif
                @if($config->email_clinica)
                    Email: {{ $config->email_clinica }}
                @endif
            </p>
        @elseif($config)
            <p style="margin: 5px 0;">{{ $config->direccion ?? '' }}</p>
            <p style="margin: 5px 0;">
                @if($config->telefono)
                    Tel: {{ $config->telefono }}
                @endif
                @if($config->telefono && $config->email)
                    | 
                @endif
                @if($config->email)
                    Email: {{ $config->email }}
                @endif
            </p>
        @endif
        <h2 style="margin-top: 20px; font-size: 18px; color: #333; border-top: 2px solid #333; padding-top: 15px;">RECETA MÉDICA</h2>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Código de Receta:</span>
            <span class="info-value">{{ $receta->codigo_receta }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($receta->fecha)->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Paciente:</span>
            <span class="info-value">{{ $receta->paciente->nombre }} {{ $receta->paciente->apellido ?? '' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Cédula:</span>
            <span class="info-value">{{ $receta->paciente->cedula ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Edad:</span>
            <span class="info-value">
                @if($receta->paciente->fecha_nacimiento)
                    {{ \Carbon\Carbon::parse($receta->paciente->fecha_nacimiento)->age }} años
                @else
                    N/A
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Doctor:</span>
            <span class="info-value">Dr. {{ $receta->doctor->nombre }} {{ $receta->doctor->apellido ?? '' }}</span>
        </div>
    </div>

    @if($receta->diagnostico)
    <div class="diagnostico">
        <h3>DIAGNÓSTICO:</h3>
        <p>{{ $receta->diagnostico }}</p>
    </div>
    @endif

    <h3 style="margin-top: 20px;">MEDICAMENTOS PRESCRITOS:</h3>
    <table class="medicamentos-table">
        <thead>
            <tr>
                <th>Medicamento</th>
                <th>Cantidad</th>
                <th>Dosis</th>
                <th>Frecuencia</th>
                <th>Duración</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receta->medicamentos as $medicamento)
            <tr>
                <td>{{ $medicamento['nombre'] }}</td>
                <td>{{ $medicamento['cantidad'] }}</td>
                <td>{{ $medicamento['dosis'] }}</td>
                <td>{{ $medicamento['frecuencia'] }}</td>
                <td>{{ $medicamento['duracion'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($receta->indicaciones)
    <div class="indicaciones">
        <h3>INDICACIONES ADICIONALES:</h3>
        <p>{{ $receta->indicaciones }}</p>
    </div>
    @endif

    <div class="firma">
        <div class="firma-line">
            <strong>Dr. {{ $receta->doctor->nombre }} {{ $receta->doctor->apellido ?? '' }}</strong><br>
            {{ $receta->doctor->especialidad ?? 'Odontólogo' }}
        </div>
    </div>

    <div class="footer">
        <p>Receta generada el {{ $fecha_impresion }}</p>
        <p>{{ $config->nombre_clinica ?? ($config->nombre ?? 'Clínica Dental') }} - Todos los derechos reservados</p>
    </div>
</body>
</html>
