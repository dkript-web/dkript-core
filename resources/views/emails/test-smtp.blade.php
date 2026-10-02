<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Conexión SMTP</title>
</head>
<body style="margin: 0; padding: 0; background-color: #030712; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #030712; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); border: 1px solid #112356;">
                    
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #030712 0%, #071026 50%, #0b1739 100%); padding: 30px 20px; border-bottom: 2px solid #10b981;">
                            <div style="display: inline-block; padding: 6px 16px; border-radius: 9999px; background-color: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); margin-bottom: 10px;">
                                <span style="color: #34d399; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;">✓ Verificación Operativa</span>
                            </div>
                            <h1 style="color: #ffffff; font-size: 20px; font-weight: 900; margin: 0;">
                                Conexión SMTP Exitosa
                            </h1>
                            <p style="color: #94a3b8; font-size: 12px; margin: 4px 0 0 0;">
                                {{ $settings->system_name ?? config('app.name', 'Dkript Core') }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px 25px;">
                            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">
                                Este es un mensaje de confirmación generado por el <strong>Panel de Administración</strong> para verificar que los parámetros de correo saliente se encuentran correctamente configurados.
                            </p>

                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 6px 0; font-size: 12px; color: #64748b;"><strong>Servidor Host:</strong></td>
                                    <td style="padding: 6px 0; font-size: 12px; color: #0f172a; font-family: monospace;">{{ $settings->mail_host ?? '127.0.0.1' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; font-size: 12px; color: #64748b;"><strong>Puerto:</strong></td>
                                    <td style="padding: 6px 0; font-size: 12px; color: #0f172a; font-family: monospace;">{{ $settings->mail_port ?? 587 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; font-size: 12px; color: #64748b;"><strong>Cifrado:</strong></td>
                                    <td style="padding: 6px 0; font-size: 12px; color: #0f172a; font-family: monospace;">{{ strtoupper($settings->mail_encryption ?? 'TLS') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; font-size: 12px; color: #64748b;"><strong>Remitente:</strong></td>
                                    <td style="padding: 6px 0; font-size: 12px; color: #0f172a;">{{ $settings->mail_from_address ?? config('mail.from.address', 'notificaciones@ejemplo.com') }}</td>
                                </tr>
                            </table>

                            <p style="color: #10b981; font-size: 13px; font-weight: 700; margin: 0;">
                                ✓ El sistema está listo para despachar correos corporativos y enlaces de restablecimiento.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color: #f1f5f9; padding: 16px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #64748b; font-size: 11px; margin: 0;">
                                &copy; {{ date('Y') }} {{ $settings->system_name ?? config('app.name', 'Dkript Core') }} • Diagnostic & Telemetry Test
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>