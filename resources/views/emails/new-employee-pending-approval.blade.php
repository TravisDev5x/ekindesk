@include('emails.partials.theme-colors')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo empleado pendiente de aprobación</title>
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
                                            HelpDesk – Recursos Humanos
                                        </h1>
                                        <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: {{ $emailTheme['muted'] }};">
                                            Nuevo empleado registrado
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="margin: 0 0 16px 0; font-size: 1rem; color: {{ $emailTheme['foreground'] }};">
                                            Recursos Humanos ha registrado un nuevo empleado: <strong>{{ $employeeName }}</strong>
                                            @if($employeeNumber ?? null)
                                                <span style="color: {{ $emailTheme['muted'] }};"> (Nº {{ $employeeNumber }})</span>
                                            @endif
                                        </p>
                                        <p style="margin: 0 0 24px 0; font-size: 1rem; color: {{ $emailTheme['body'] }};">
                                            Por favor, ingresa al sistema para aprobar su cuenta y asignarle los roles técnicos correspondientes.
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="left">
                                            <tr>
                                                <td align="center" style="border-radius: 8px; background-color: {{ $emailTheme['primary'] }};">
                                                    <a href="{{ url('/users') }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 10px 20px; font-size: 0.875rem; font-weight: 500; color: {{ $emailTheme['primary_fg'] }}; text-decoration: none;">
                                                        Ir a Usuarios
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="clear: both; height: 24px;"></div>
                                        <p style="margin: 0; font-size: 0.8125rem; color: {{ $emailTheme['muted'] }};">
                                            Este correo se ha enviado a los administradores con permiso de gestión de usuarios.
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
