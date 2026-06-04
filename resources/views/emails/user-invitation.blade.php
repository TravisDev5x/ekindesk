@include('emails.partials.theme-colors')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación a HelpDesk</title>
</head>
<body style="margin: 0; padding: 0; background-color: {{ $emailTheme['page_bg'] }}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 16px; line-height: 1.6; color: {{ $emailTheme['foreground'] }};">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: {{ $emailTheme['page_bg'] }};">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 520px;">
                    <tr>
                        <td style="background-color: {{ $emailTheme['card'] }}; border: 1px solid {{ $emailTheme['border'] }}; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06); overflow: hidden;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding: 24px 24px 16px 24px; border-bottom: 1px solid {{ $emailTheme['border'] }};">
                                        <h1 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: {{ $emailTheme['foreground'] }}; letter-spacing: -0.025em;">
                                            HelpDesk
                                        </h1>
                                        <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: {{ $emailTheme['muted'] }};">
                                            Invitación para unirte al sistema
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="margin: 0 0 16px 0; font-size: 1rem; color: {{ $emailTheme['foreground'] }};">
                                            <strong>{{ $inviterName }}</strong> te ha invitado a unirte a HelpDesk
                                            @if(!empty($clientName))
                                                en <strong>{{ $clientName }}</strong>
                                            @endif
                                            .
                                        </p>
                                        @if(!empty($roleName))
                                        <p style="margin: 0 0 16px 0; font-size: 0.9375rem; color: {{ $emailTheme['body'] }};">
                                            Rol sugerido: <strong>{{ $roleName }}</strong> (un administrador confirmará tus permisos finales).
                                        </p>
                                        @else
                                        <p style="margin: 0 0 16px 0; font-size: 0.9375rem; color: {{ $emailTheme['body'] }};">
                                            Tras activar tu cuenta, un administrador te asignará el rol y permisos según tu puesto.
                                        </p>
                                        @endif
                                        <p style="margin: 0 0 24px 0; font-size: 1rem; color: {{ $emailTheme['body'] }};">
                                            Haz clic en el botón para configurar tu acceso (contraseña o Google, si tu organización lo permite).
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="left">
                                            <tr>
                                                <td align="center" style="border-radius: 8px; background-color: {{ $emailTheme['primary'] }};">
                                                    <a href="{{ $acceptUrl }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 10px 20px; font-size: 0.875rem; font-weight: 500; color: {{ $emailTheme['primary_fg'] }}; text-decoration: none;">
                                                        Aceptar invitación
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="clear: both; height: 24px;"></div>
                                        <p style="margin: 0 0 8px 0; font-size: 0.8125rem; color: {{ $emailTheme['muted'] }};">
                                            Este enlace expira en 48 horas ({{ $expiresAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}).
                                        </p>
                                        <p style="margin: 0; font-size: 0.75rem; color: {{ $emailTheme['footer_muted'] }}; word-break: break-all;">
                                            Si el botón no funciona, copia y pega: <a href="{{ $acceptUrl }}" style="color: {{ $emailTheme['link'] }}; text-decoration: underline;">{{ $acceptUrl }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
