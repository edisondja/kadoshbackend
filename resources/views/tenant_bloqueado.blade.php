<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Bloqueado</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #2d2d2f;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .message {
            color: #6c757d;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
        }
        
        .details h3 {
            color: #495057;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .details p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .details strong {
            color: #2d2d2f;
        }
        
        .contact {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        
        .contact p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <h1>Acceso Denegado</h1>
        <div class="message">
            {{ $mensaje }}
        </div>
        
        @if(isset($tenant))
        <div class="details">
            <h3>Información del Sistema</h3>
            <p><strong>Nombre:</strong> {{ $tenant->nombre }}</p>
            @if($tenant->fecha_vencimiento)
                <p><strong>Fecha de Vencimiento:</strong> {{ $tenant->fecha_vencimiento->format('d/m/Y') }}</p>
            @endif
            @if($tenant->contacto_email)
                <p><strong>Contacto:</strong> {{ $tenant->contacto_email }}</p>
            @endif
        </div>
        @endif
        
        <div class="contact">
            <p>Por favor, contacte al administrador del sistema</p>
            <p>para más información sobre su licencia.</p>
        </div>
    </div>
</body>
</html>
