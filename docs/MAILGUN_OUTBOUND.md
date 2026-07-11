# Mailgun — correo transaccional saliente

Checklist para verificar en el [dashboard de Mailgun](https://app.mailgun.com/) antes
de dar por buena la configuración de `MAIL_MAILER=mailgun` en producción. No configura
DNS por ti — es la lista de qué revisar.

## Credenciales (`.env`, no confundir con el webhook inbound)

| Variable | De dónde sale |
|---|---|
| `MAILGUN_DOMAIN` | Dominio de envío verificado en Mailgun (ej. `mg.tikara.mx` o `tikara.mx`) |
| `MAILGUN_SECRET` | Sending API key del dominio (Mailgun → Settings → API Keys) |
| `MAILGUN_ENDPOINT` | `api.mailgun.net` (US) o `api.eu.mailgun.net` (EU) — según la región del dominio |

`MAILGUN_WEBHOOK_SIGNING_KEY` es una credencial **distinta**, solo para verificar la
firma HMAC del webhook *inbound* (`InboundEmailService::verifyMailgunSignature`). No
se usa para enviar correo.

## Registros DNS a verificar en el dominio de envío

Mailgun los genera automáticamente al agregar el dominio — solo hay que confirmar que
están publicados y en estado "Verified" (Mailgun → Sending → Domains → tu dominio):

- **SPF** (TXT en la raíz o subdominio de envío): debe incluir
  `include:mailgun.org`. Si el dominio ya tiene un SPF de otro proveedor, hay que
  fusionarlo en un solo registro — dos TXT `v=spf1` en el mismo nombre rompen SPF.
- **DKIM** (TXT, nombre tipo `smtp._domainkey.<dominio>` o similar, lo da Mailgun
  exacto): clave pública que Mailgun usa para firmar los correos salientes.
- **CNAME de tracking** (opcional, para tracking de opens/clicks de Mailgun): apunta
  a `mailgun.org`.
- **DMARC** (TXT en `_dmarc.<dominio>`): Mailgun no lo exige para verificar el
  dominio, pero sin él muchos proveedores (Gmail, Outlook) marcan el correo como
  sospechoso o lo mandan a spam. Empezar con `p=none` para solo monitorear, subir a
  `p=quarantine`/`p=reject` una vez confirmado que todo el correo legítimo pasa SPF/DKIM.

## Antes de activar en producción

1. Dominio en estado "Verified" en el dashboard (no "Unverified" ni "Pending").
2. Enviar un correo de prueba real y revisar los headers (`Authentication-Results`)
   en la bandeja de destino — confirmar `spf=pass`, `dkim=pass`.
3. Si el dominio es nuevo (sin historial de envío), Mailgun/los proveedores de correo
   aplican límites de reputación más estrictos al inicio — evitar un volumen alto de
   golpe los primeros días.
