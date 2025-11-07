<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credenciales de Acceso</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .header img {
            max-width: 200px;
            height: auto;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .message {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .credentials {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }

        .credential-item {
            margin: 10px 0;
            color: #374151;
        }

        .credential-label {
            font-weight: bold;
            color: #1f2937;
        }

        .credential-value {
            color: #2563eb;
            font-family: monospace;
            font-size: 14px;
        }

        .button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }

        .button:hover {
            background-color: #1d4ed8;
        }

        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
        }

        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            margin: 20px 0;
            color: #92400e;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header con Logo -->
        <!-- Header con Logo -->
        <div class="header">
            <img src="https://i.ibb.co/YF41L8L5/logo.png"
                alt="Ruta D" style="max-width: 250px; height: auto; display: block; margin: 0 auto;">
        </div>

        <!-- Contenido -->
        <div class="content">
            <div class="greeting">¡Hola {{ $entrepreneurName }}!</div>

            <p class="message">
                Te damos la bienvenida a la plataforma <strong>Ruta D</strong>.
            </p>

            <p class="message">
                Tus credenciales de acceso han sido creadas exitosamente:
            </p>

            <div class="credentials">
                <div class="credential-item">
                    <span class="credential-label">Correo electrónico:</span><br>
                    <span class="credential-value">{{ $email }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Contraseña temporal:</span><br>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Iniciar Sesión</a>
            </div>

            <p class="message" style="margin-top: 30px;">
                Si no solicitaste estas credenciales, por favor ignora este correo.
            </p>

            <p class="message">
                <strong>Saludos cordiales,</strong><br>
                Equipo Ruta D
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            © {{ date('Y') }} Ruta D. Todos los derechos reservados.
        </div>
    </div>
</body>

</html>
