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
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
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
        @if($config && $config->logo)
            <img src="{{ public_path('storage/' . $config->logo) }}" alt="Logo" style="max-height: 80px; margin-bottom: 10px;">
        @endif
        <h1>{{ $config->nombre_clinica ?? 'CLÍNICA DENTAL' }}</h1>
        <p>{{ $config->direccion ?? '' }}</p>
        <p>Tel: {{ $config->telefono ?? '' }} | Email: {{ $config->email ?? '' }}</p>
        <h2 style="margin-top: 15px; font-size: 18px;">RECETA MÉDICA</h2>
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
        <p>{{ $config->nombre_clinica ?? 'Clínica Dental' }} - Todos los derechos reservados</p>
    </div>
</body>
</html>
