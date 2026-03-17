<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recibo</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #f6f7fb;
            color: #0f172a;
        }
        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 18px 14px 28px;
        }
        .card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(2, 6, 23, 0.06);
            overflow: hidden;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        .title {
            font-weight: 800;
            letter-spacing: 0.2px;
            font-size: 14px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.14);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            white-space: nowrap;
        }
        .btn:hover { background: rgba(255,255,255,0.20); }
        .content {
            padding: 12px;
        }
        iframe {
            width: 100%;
            height: 80vh;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            background: #fff;
        }
        .hint {
            margin-top: 10px;
            color: #64748b;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="topbar">
                <div class="title">Recibo / Documento</div>
                <a class="btn" href="{{ $pdfUrl }}" download>
                    Descargar PDF
                </a>
            </div>
            <div class="content">
                <iframe src="{{ $pdfUrl }}" title="Recibo PDF"></iframe>
                <div class="hint">
                    Si no se visualiza, use el botón <strong>Descargar PDF</strong>.
                </div>
            </div>
        </div>
    </div>
</body>
</html>

