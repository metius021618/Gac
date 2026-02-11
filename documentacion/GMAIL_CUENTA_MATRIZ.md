# Gmail: cuenta matriz

La **cuenta matriz** es la cuenta Gmail que recibe los reenvíos de los usuarios. El sistema la lee con la Gmail API y obtiene el destinatario real desde los headers del correo (`To`, `X-Original-To`).

## 1. Configurar Gmail API en .env

En la raíz del proyecto, en `.env`, define:

```env
GMAIL_CLIENT_ID=tu_client_id.apps.googleusercontent.com
GMAIL_CLIENT_SECRET=tu_client_secret
GMAIL_REDIRECT_URI=https://TU_DOMINIO/gmail/callback
GMAIL_SCOPES=https://www.googleapis.com/auth/gmail.readonly
```

- Crea (o usa) un proyecto en [Google Cloud Console](https://console.cloud.google.com/).
- Activa la **Gmail API**.
- Crea credenciales **OAuth 2.0** (tipo “Aplicación web”).
- En “URIs de redirección autorizados” añade exactamente la misma URL que `GMAIL_REDIRECT_URI` (ej: `https://app.pocoyoni.com/gmail/callback`).

Más detalle en `documentacion/CONFIGURACION_ENV.md`.

## 2. Conectar la cuenta matriz en el panel

Solo puede haber **una** cuenta Gmail matriz. Se configura desde Configuración del sistema:

1. Entra al panel de admin (sesión iniciada).
2. Ve a **Configuración** (menú del panel).
3. En la sección **“Configurar cuenta Google (Gmail matriz)”** pulsa **“Configurar cuenta Gmail matriz”** (o **“Cambiar cuenta Gmail matriz”** si ya hay una).
4. Inicia sesión en Google con la cuenta Gmail que recibe los reenvíos y acepta los permisos de solo lectura.
5. Tras autorizar, volverás a Configuración; la cuenta quedará guardada como la única Gmail. Si había otra, se sustituye.

El cron lee solo esa cuenta (la única con `type = 'gmail'` en `email_accounts`).

## 3. Qué hace el sistema

- El **cron** (`email_reader_gmail.py`, por ejemplo desde `run_readers_loop_30s.sh`) lista las cuentas con `type = 'gmail'` y lee cada una con la Gmail API.
- Para cada correo, el destinatario real se toma de los headers (`To`, `X-Original-To`), no del buzón, así que los reenvíos se asocian al correo correcto.
- Los códigos se guardan en `codes` con `recipient_email` = destinatario real y `origin = 'gmail'`.

## 4. Ver que la cuenta está conectada

- **Panel**: **Dashboard** → tarjeta “Gmail” (número de cuentas) o **Correos** → filtro **Gmail**. Ahí debe aparecer la cuenta matriz.
- **Base de datos**: en `email_accounts` debe haber una fila con `email` = la cuenta matriz y `type = 'gmail'`, con `oauth_refresh_token` rellenado.

## 5. Varias cuentas Gmail

Puedes conectar más de una cuenta Gmail (varias matrices o una matriz y otras cuentas). El cron las procesa todas. Cada una se conecta con el mismo botón “Conectar Gmail (cuenta matriz)” y eligiendo en Google la cuenta a autorizar.
