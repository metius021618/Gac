# Gmail event-driven: setup desde cero (lenninstreaming@gmail.com)

Guía paso a paso para dejar el sistema OTP Gmail funcionando con **una sola cuenta Google** (lenninstreaming@gmail.com): proyecto en Cloud, OAuth, Pub/Sub, webhook y watch. Todo en la misma cuenta evita problemas de autorización y refresh token.

---

## Requisitos previos

- Iniciar sesión en Google con **lenninstreaming@gmail.com**.
- Tener acceso al servidor donde está la app (app.pocoyoni.com) para editar `.env` y ejecutar scripts.

---

## 1. Crear proyecto en Google Cloud

1. Entra en [Google Cloud Console](https://console.cloud.google.com/).
2. Asegúrate de estar con **lenninstreaming@gmail.com** (arriba a la derecha).
3. Arriba: clic en el selector de proyecto → **“Nuevo proyecto”**.
4. **Nombre del proyecto:** por ejemplo `GAC-StreaminLenin` (o el que prefieras).
5. Clic en **“Crear”**.
6. Cuando termine, selecciona ese proyecto en la barra superior.

---

## 2. Habilitar APIs

1. Menú (☰) → **“APIs y servicios”** → **“Biblioteca”**.
2. Busca **“Gmail API”** → entra → **“Habilitar”**.
3. Vuelve a “Biblioteca”, busca **“Cloud Pub/Sub API”** → entra → **“Habilitar”**.

---

## 3. Crear credenciales OAuth 2.0 (cliente Web)

1. Menú → **“APIs y servicios”** → **“Credenciales”**.
2. **“+ Crear credenciales”** → **“ID de cliente de OAuth”**.
3. Si te pide “Pantalla de consentimiento”:
   - Tipo: **Externo**.
   - Nombre de la app: p. ej. `GAC Gmail`.
   - Correo de asistencia: **lenninstreaming@gmail.com**.
   - Guardar. En “Usuarios de prueba” añade **lenninstreaming@gmail.com** si hace falta.
4. Volver a Credenciales → **“+ Crear credenciales”** → **“ID de cliente de OAuth”**.
5. **Tipo de aplicación:** “Aplicación web”.
6. **Nombre:** p. ej. `GAC Web`.
7. **URIs de redirección autorizados** → “+ Añadir URI”:
   - **`https://app.pocoyoni.com/gmail/callback`** — La que usa la app al hacer clic en "Conectar Gmail" o "Configurar cuenta Gmail matriz". Google redirige a tu dominio. Debe coincidir con `GMAIL_REDIRECT_URI` en `.env`. **Principal.**
   - **`http://localhost:8090/`** (opcional) — Solo para el script `generate_token.py` en tu PC; ese script usa un servidor local, por eso localhost.
8. **“Crear”**.
9. Copia y guarda:
   - **ID de cliente** (termina en `.apps.googleusercontent.com`).
   - **Secreto de cliente** (empieza por `GOCSPX-`).

En `.env` del servidor: `GMAIL_CLIENT_ID`, `GMAIL_CLIENT_SECRET` y **`GMAIL_REDIRECT_URI=https://app.pocoyoni.com/gmail/callback`** (para que "Conectar Gmail" desde el panel funcione).

---

## 4. Crear topic y suscripción en Pub/Sub

### 4.1 Topic

1. Menú → **“Pub/Sub”** → **“Topics”** (Temas).
2. **“Create topic”** / **“Crear tema”**.
3. **Topic ID:** p. ej. `gac-gmail-push`.
4. **“Create”** / **“Crear”**.

Anota el **ID del proyecto** (p. ej. `gac-streaminlenin-123456`). El nombre completo del topic será:
`projects/<ID_PROYECTO>/topics/gac-gmail-push`.

### 4.2 Permiso para que Gmail publique en el topic

1. En la lista de topics, abre el topic `gac-gmail-push`.
2. Pestaña **“Permissions”** / **“Permisos”**.
3. **“Add principal”** / **“Añadir principal”**.
4. **Nuevo principal:**  
   `gmail-api-push@system.gserviceaccount.com`
5. **Rol:** “Pub/Sub Publisher” / “Publicador”.
6. Guardar.

### 4.3 Suscripción tipo Push (webhook)

**Si ya tienes la suscripción** (p. ej. `gac-gmail-push-sub`) en el proyecto, omite este paso y usa la existente.

1. Menú Pub/Sub → **“Subscriptions”** / **“Suscripciones”**.
2. **“Create subscription”** / **“Crear suscripción”**.
3. **Subscription ID:** p. ej. `gac-gmail-push-sub`.
4. **Topic:** el que creaste (`gac-gmail-push`).
5. **Delivery type:** “Push”.
6. **Endpoint URL:**  
   `https://app.pocoyoni.com/gmail/push`
   (sin barra final o con `/` según cómo esté configurado tu endpoint; normalmente ambas funcionan).
7. Crear.

---

## 5. Generar refresh token (lenninstreaming@gmail.com)

El refresh token debe generarse con la cuenta que luego usará Gmail (la cuenta matriz).

1. En tu **PC** (donde tengas navegador), clona o copia el proyecto y entra en la carpeta del proyecto (donde está `cron/`).
2. Opcional: en `cron/generate_token.py` asegúrate de que `CLIENT_CONFIG` use el **mismo** Client ID y Client Secret que creaste en el paso 3 (o que los lea de `.env` si lo adaptas).
3. En Google Cloud, el cliente OAuth debe tener en “URIs de redirección autorizados” la URL del script si usas generate_token.py (`http://localhost:8090/`) y la de la app: `https://app.pocoyoni.com/gmail/callback`.
4. Ejecuta:
   ```bash
   python3 cron/generate_token.py
   ```
5. Se abrirá el navegador; inicia sesión con **lenninstreaming@gmail.com** y autoriza.
6. En la terminal aparecerá algo como:  
   `Refresh Token: 1//0gXXXXXXXX...`  
   Copia ese valor completo (es el refresh token de esa cuenta).

Ese token es el que usarás en el servidor para la cuenta Gmail matriz.

---

## 6. Configurar .env en el servidor

En el servidor donde corre la app (app.pocoyoni.com), edita el `.env` en la raíz del proyecto y deja algo como:

```env
# Gmail (cuenta lenninstreaming - proyecto GAC-StreaminLenin)
GMAIL_CLIENT_ID=32967724133-xxxx.apps.googleusercontent.com
GMAIL_CLIENT_SECRET=GOCSPX-xxxx
# Redirect para "Conectar Gmail" desde el panel (debe estar en Google Console)
GMAIL_REDIRECT_URI=https://app.pocoyoni.com/gmail/callback

# Topic Pub/Sub (el que creaste en el paso 4)
# Formato: projects/<ID_PROYECTO>/topics/<TOPIC_ID>
GMAIL_PUBSUB_TOPIC=projects/TU_ID_DE_PROYECTO/topics/gac-gmail-push

# Opcional: URL del webhook (ya está por defecto en código)
# GMAIL_WEBHOOK_URL=https://app.pocoyoni.com/gmail/push
```

Sustituye:

- `GMAIL_CLIENT_ID` y `GMAIL_CLIENT_SECRET` por los del paso 3.
- `TU_ID_DE_PROYECTO` por el ID del proyecto de Google Cloud (p. ej. `gac-streaminlenin-123456`).

La **cuenta Gmail matriz** (la que recibe los correos y para la que vale el refresh token) debe estar configurada en la app: en **Configuración** → cuenta Gmail matriz, asociada a la cuenta de **email_accounts** que tenga guardado el **refresh token** que generaste en el paso 5. Si la app ya tiene flujo de “Conectar Gmail”, puedes usarlo con lenninstreaming@gmail.com y pegar ahí el refresh token, o guardarlo en la BD en la cuenta que uses como matriz.

---

## 7. Registrar el Watch (primera vez y renovación)

En el servidor, desde la raíz del proyecto (donde está `cron/`):

```bash
cd /ruta/completa/a/app.pocoyoni.com
python3 cron/renew_gmail_watch.py
```

Deberías ver algo como:

- `Watch expiration recibido: ...`
- `gmail_last_history_id guardado: ...`
- `Renovación Gmail Watch completada.`

Para que no expire (~7 días), programa un cron diario, por ejemplo a las 3:00:

```bash
0 3 * * * cd /ruta/completa/a/app.pocoyoni.com && python3 cron/renew_gmail_watch.py >> logs/renew_gmail_watch.log 2>&1
```

---

## 8. Comprobar que el push llega

1. Envía un correo a la cuenta que está configurada como Gmail matriz (por ejemplo lenninstreaming@gmail.com).
2. Revisa los logs del servidor:
   - Si el webhook es PHP: logs del servidor web o del script que recibe el POST.
   - Si usas el worker Python: logs de `process_gmail_history.py` o del cron.
3. Deberías ver que se recibe el push y se procesa el correo (y, si aplica, que se guarda el código OTP).

Para depurar el endpoint del webhook:

```bash
curl -X POST https://app.pocoyoni.com/gmail/push -H "Content-Type: application/json" -d '{}'
```

(Responderá 200 aunque el body esté vacío; un push real de Google trae un JSON con `message.data`.)

---

## Resumen de datos que debes tener

| Dónde | Qué |
|-------|-----|
| Google Cloud – Proyecto | Creado con lenninstreaming@gmail.com |
| APIs | Gmail API y Pub/Sub API habilitadas |
| Credenciales OAuth | Client ID (Web) + Client Secret + URIs: `https://app.pocoyoni.com/gmail/callback` y (opcional) `http://localhost:8090/` |
| Pub/Sub | Topic (ej. `gac-gmail-push`) + permiso a `gmail-api-push@system.gserviceaccount.com` |
| Pub/Sub | Suscripción Push con endpoint `https://app.pocoyoni.com/gmail/push` |
| generate_token.py | Refresh token generado con lenninstreaming@gmail.com |
| .env | GMAIL_CLIENT_ID, GMAIL_CLIENT_SECRET, GMAIL_PUBSUB_TOPIC |
| App / BD | Cuenta matriz = cuenta que tiene ese refresh token |
| Cron | renew_gmail_watch.py diario |

Con esto, el flujo queda igual que el que tenías para “developer”, pero usando **lenninstreaming@gmail.com** y el proyecto “streaminlenin” en Google Cloud, con un solo lugar donde gestionar OAuth, Pub/Sub y Watch.
