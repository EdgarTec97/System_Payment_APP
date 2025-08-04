<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica tu email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <h2>Verifica tu dirección de email</h2>
    </div>
    
    <div class="content">
        <p>Hola {{ $user->first_name }},</p>
        
        <p>¡Bienvenido a {{ config('app.name') }}! Para completar tu registro y comenzar a usar tu cuenta, necesitas verificar tu dirección de email.</p>
        
        <p>Haz clic en el siguiente botón para verificar tu email:</p>
        
        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">Verificar Email</a>
        </div>
        
        <p>Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu navegador:</p>
        <p style="word-break: break-all; background-color: #f3f4f6; padding: 10px; border-radius: 4px;">
            {{ $verificationUrl }}
        </p>
        
        <div class="warning">
            <strong>⚠️ Importante:</strong> Este enlace expirará el {{ $expiresAt->format('d/m/Y H:i') }}. Si no verificas tu email antes de esa fecha, tendrás que solicitar un nuevo enlace de verificación.
        </div>
        
        <p>Si no creaste una cuenta en {{ config('app.name') }}, puedes ignorar este email de forma segura.</p>
        
        <p>¡Gracias por unirte a nosotros!</p>
        <p>El equipo de {{ config('app.name') }}</p>
    </div>
    
    <div class="footer">
        <p>Este es un email automático, por favor no respondas a este mensaje.</p>
        <p>Si tienes problemas para verificar tu email, contacta nuestro soporte.</p>
    </div>
</body>
</html>

