<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receta Médica</title>
</head>
<body>
    <h2>Receta Médica - {{ $receta->codigo_receta }}</h2>
    
    <p>Estimado/a paciente,</p>
    
    <p>Adjunto encontrará su receta médica emitida el {{ \Carbon\Carbon::parse($receta->fecha)->format('d/m/Y') }}.</p>
    
    <p><strong>Paciente:</strong> {{ $receta->paciente->nombre }} {{ $receta->paciente->apellido ?? '' }}</p>
    <p><strong>Doctor:</strong> Dr. {{ $receta->doctor->nombre }} {{ $receta->doctor->apellido ?? '' }}</p>
    
    @if($receta->diagnostico)
    <p><strong>Diagnóstico:</strong> {{ $receta->diagnostico }}</p>
    @endif
    
    <p><strong>Medicamentos prescritos:</strong></p>
    <ul>
        @foreach($receta->medicamentos as $medicamento)
        <li>
            <strong>{{ $medicamento['nombre'] }}</strong><br>
            Cantidad: {{ $medicamento['cantidad'] }}<br>
            Dosis: {{ $medicamento['dosis'] }}<br>
            Frecuencia: {{ $medicamento['frecuencia'] }}<br>
            Duración: {{ $medicamento['duracion'] }}
        </li>
        @endforeach
    </ul>
    
    @if($receta->indicaciones)
    <p><strong>Indicaciones adicionales:</strong></p>
    <p>{{ $receta->indicaciones }}</p>
    @endif
    
    <p>Por favor, consulte con su médico antes de tomar cualquier medicamento.</p>
    
    <p>Saludos cordiales,<br>
    {{ $config->nombre_clinica ?? 'Clínica Dental' }}</p>
</body>
</html>
