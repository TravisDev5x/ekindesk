@php /** @var string $url */ @endphp
@include('emails.partials.theme-colors')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('passwords.subject') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: {{ $emailTheme['page_bg'] }}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 16px; line-height: 1.6; color: {{ $emailTheme['foreground'] }};">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: {{ $emailTheme['page_bg'] }};">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 480px;">
                    <tr>
                        <td style="background-color: {{ $emailTheme['card'] }}; border: 1px solid {{ $emailTheme['border'] }}; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06); overflow: hidden;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding: 24px 24px 16px 24px; border-bottom: 1px solid {{ $emailTheme['border'] }};">
                                        <h1 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: {{ $emailTheme['foreground'] }}; letter-spacing: -0.025em;">
                                            Tikara
                                        </h1>
                                        <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: {{ $emailTheme['muted'] }};">
                                            {{ __('passwords.subject') }}
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="margin: 0 0 16px 0; font-size: 1rem; color: {{ $emailTheme['body'] }};">
                                            {{ __('passwords.line_1') }}
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="left">
                                            <tr>
                                                <td align="center" style="border-radius: 8px; background-color: {{ $emailTheme['primary'] }};">
                                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 10px 20px; font-size: 0.875rem; font-weight: 500; color: {{ $emailTheme['primary_fg'] }}; text-decoration: none;">
                                                        {{ __('passwords.button') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="clear: both; height: 24px;"></div>
                                        <p style="margin: 0; font-size: 0.8125rem; color: {{ $emailTheme['muted'] }};">
                                            {{ __('passwords.line_2') }}
                                        </p>
                                        <p style="margin: 8px 0 0 0; font-size: 0.8125rem;">
                                            <a href="{{ $url }}" style="color: {{ $emailTheme['link'] }}; text-decoration: underline; word-break: break-all;">{{ $url }}</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 24px 24px 24px; border-top: 1px solid {{ $emailTheme['border'] }}; background-color: {{ $emailTheme['footer_bg'] }};">
                                        <p style="margin: 0; font-size: 0.75rem; color: {{ $emailTheme['muted'] }};">
                                            {{ __('passwords.line_3') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 24px; text-align: center;">
                            <p style="margin: 0; font-size: 0.75rem; color: {{ $emailTheme['footer_muted'] }};">
                                Tikara
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
