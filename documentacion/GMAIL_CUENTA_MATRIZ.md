# Gmail: cuenta matriz

La **cuenta matriz** es la cuenta Gmail que recibe los reenvÃ­os de los usuarios. El sistema la lee con la Gmail API y obtiene el destinatario real desde los headers del correo (`To`, `X-Original-To`).

## 1. Configurar Gmail API en .env

En la raÃ­z del proyecto, en `.env`, define:

```env
GMAIL_CLIENT_ID=tu_client_id.apps.googleusercontent.com
GMAIL_CLIENT_SECRET=tu_client_secret
GMAIL_REDIRECT_URI=https://TU_DOMINIO/gmail/callback
GMAIL_SCOPES=https://www.googleapis.com/auth/gmail.readonly
```

- Crea (o usa) un proyecto en [Google Cloud Console](https://console.cloud.google.com/).
- Activa la **Gmail API**.
- Crea credenciales **OAuth 2.0** (tipo â€œAplicaciÃ³n webâ€).
- En â€œURIs de redirecciÃ³n autorizadosâ€ aÃ±ade exactamente la misma URL que `GMAIL_REDIRECT_URI` (ej: `https://app.pocoyoni.com/gmail/callback`).

MÃ¡s detalle en `documentacion/CONFIGURACION_ENV.md`.

## 2. Conectar la cuenta matriz en el panel

Solo puede haber **una** cuenta Gmail matriz. Se configura desde ConfiguraciÃ³n del sistema:

1. Entra al panel de admin (sesiÃ³n iniciada).
2. Ve a **ConfiguraciÃ³n** (menÃº del panel).
3. En la secciÃ³n **â€œConfigurar cuenta Google (Gmail matriz)â€** pulsa **â€œConfigurar cuenta Gmail matrizâ€** (o **â€œCambiar cuenta Gmail matrizâ€** si ya hay una).
4. Inicia sesiÃ³n en Google con la cuenta Gmail que recibe los reenvÃ­os y acepta los permisos de solo lectura.
5. Tras autorizar, volverÃ¡s a ConfiguraciÃ³n; la cuenta quedarÃ¡ guardada como la Ãºnica Gmail. Si habÃ­a otra, se sustituye.

El cron lee solo esa cuenta (la Ãºnica con `type = 'gmail'` en `email_accounts`).

## 3. QuÃ© hace el sistema

- El **cron** (`email_reader_gmail.py`, por ejemplo desde `run_readers_loop_30s.sh`) lista las cuentas con `type = 'gmail'` y lee cada una con la Gmail API.
- Para cada correo, el destinatario real se toma de los headers (`To`, `X-Original-To`), no del buzÃ³n, asÃ­ que los reenvÃ­os se asocian al correo correcto.
- Los cÃ³digos se guardan en `codes` con `recipient_email` = destinatario real y `origin = 'gmail'`.

## 4. Ver que la cuenta estÃ¡ conectada

- **Panel**: **Dashboard** â†’ tarjeta â€œGmailâ€ (nÃºmero de cuentas) o **Correos** â†’ filtro **Gmail**. AhÃ­ debe aparecer la cuenta matriz.
- **Base de datos**: en `email_accounts` debe haber una fila con `email` = la cuenta matriz y `type = 'gmail'`, con `oauth_refresh_token` rellenado.

## 5. Una sola cuenta

En este sistema solo hay **una** cuenta Gmail matriz. Si configuras otra desde ConfiguraciÃ³n, la anterior se elimina y solo queda la nueva.