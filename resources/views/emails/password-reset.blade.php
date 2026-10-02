<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
</head>
<body style="margin: 0; padding: 0; background-color: #030712; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #030712; padding: 40px 10px;">
        <tr>
            <td align="center">
                <!-- Tarjeta Principal -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); border: 1px solid #112356;">
                    
                    <!-- Encabezado Superior con Branding Dkript -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #030712 0%, #071026 50%, #0b1739 100%); padding: 35px 25px; border-bottom: 2px solid #00d4ff;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <div style="display: inline-block; padding: 6px 16px; border-radius: 9999px; background-color: rgba(0, 212, 255, 0.12); border: 1px solid rgba(0, 212, 255, 0.4); margin-bottom: 12px;">
                                            <span style="color: #00d4ff; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;">Seguridad Corporativa</span>
                                        </div>
                                        <h1 style="color: #ffffff; font-size: 22px; font-weight: 900; margin: 0; tracking-tight: -0.5px;">
                                            {{ $settings->system_name ?? config('app.name', 'Dkript Core') }}
                                        </h1>
                                        <p style="color: #94a3b8; font-size: 12px; margin: 4px 0 0 0;">
                                            {{ config('dkript.edition', 'Core') }} Platform
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Contenido Central -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <h2 style="color: #0f172a; font-size: 18px; font-weight: 800; margin: 0 0 16px 0;">
                                Hola, {{ $user->name }}
                            </h2>

                            <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hemos recibido una solicitud para restablecer la contraseña de acceso a tu cuenta en <strong>{{ $settings->system_name ?? config('app.name', 'Dkript Core') }}</strong>.
                            </p>

                            <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 25px 0;">
                                Para definir tus nuevas credenciales y recuperar el acceso seguro a la plataforma, haz clic en el siguiente botón:
                            </p>

                            <!-- Botón de Acción Principal -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $resetUrl }}" 
                                           style="display: inline-block; background-color: #0062f5; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-size: 14px; font-weight: 700; box-shadow: 0 6px 20px rgba(0, 98, 245, 0.35); text-align: center;">
                                            Restablecer Mi Contraseña &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Caja Informativa de Seguridad -->
                            <div style="background-color: #f8fafc; border-left: 4px solid #0062f5; padding: 16px; border-radius: 8px; margin-bottom: 25px;">
                                <p style="color: #334155; font-size: 12px; line-height: 1.5; margin: 0;">
                                    <strong>Nota de seguridad:</strong> Este enlace de restablecimiento es de un solo uso y expirará automáticamente en <strong>60 minutos</strong>. Si no solicitaste este cambio, no se requiere ninguna acción; tu cuenta permanece completamente segura.
                                </p>
                            </div>

                            <!-- Enlace Alternativo Plano -->
                            <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 0 0 8px 0;">
                                Si el botón no funciona, copia y pega la siguiente URL en tu navegador:
                            </p>
                            <p style="margin: 0; word-break: break-all;">
                                <a href="{{ $resetUrl }}" style="color: #0062f5; font-size: 11px; text-decoration: underline;">
                                    {{ $resetUrl }}
                                </a>
                            </p>
                        </td>
                    </tr>

                    <!-- Pie de Página -->
                    <tr>
                        <td align="center" style="background-color: #f1f5f9; padding: 20px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #64748b; font-size: 11px; margin: 0; line-height: 1.5;">
                                &copy; {{ date('Y') }} {{ $settings->system_name ?? config('app.name', 'Dkript Core') }}. Todos los derechos reservados.<br>
                                Sistema de Gestión y Administración Corporativa
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>