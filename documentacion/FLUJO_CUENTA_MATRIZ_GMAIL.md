# Flujo y gestión de la cuenta Gmail matriz

Este documento explica cómo funciona la cuenta matriz, qué API se usa y cómo se gestiona de punta a punta.

---

## 1. Qué es la cuenta matriz

- **Una sola** cuenta Gmail que recibe los correos (por ejemplo reenvíos de muchos usuarios a esa dirección).
- El sistema **lee solo esa cuenta** y extrae códigos de los correos.
- Para cada correo, el **destinatario real** (a quién iba dirigido) se obtiene de los headers del mensaje, no del buzón. Así, si alguien reenvía a la matriz un correo que originalmente iba a `usuario@gmail.com`, el código se guarda asociado a `usuario@gmail.com`.

---

## 2. API que se usa: Gmail API (la que ya tenemos)

Se usa **solo la Gmail API de Google**, con OAuth 2.0 y permiso de **solo lectura**. No hace falta otra API.

| Qué | Dónde |
|-----|--------|
| **API** | [Gmail API](https://developers.google.com/gmail/api) (v1) |
| **Alcance (scope)** | `https://www.googleapis.com/auth/gmail.readonly` |
| **Uso** | Listar mensajes del INBOX, leer mensaje completo (headers + cuerpo) |

Con eso el sistema puede:

- Listar mensajes de la cuenta (`users.messages.list`).
- Obtener cada mensaje con `format='full'` (`users.messages.get`) para tener headers (To, From, Subject, X-Original-To, etc.) y cuerpo.
- Extraer destinatario real desde `To` / `X-Original-To` y guardar los códigos con ese `recipient_email`.

**Conclusión:** La API que ya usamos es la correcta; no hay que cambiar a otra.

---

## 3. Flujo completo (resumido)

```
[Admin] Configuración → "Configurar cuenta Gmail matriz"
        ↓
[PHP]   /gmail/connect?from=settings → redirige a Google OAuth (state=from=settings)
        ↓
[Usuario] Inicia sesión en Google y acepta "ver tus correos (solo lectura)"
        ↓
[Google] Redirige a /gmail/callback?code=...&state=from=settings
        ↓
[PHP]   GmailController@callback:
        - Intercambia code por access_token + refresh_token
        - Obtiene el email de la cuenta (Gmail API users.getProfile)
        - Guarda/actualiza en email_accounts (type=gmail, oauth_refresh_token)
        - Si from=settings: borra cualquier otra cuenta Gmail (solo queda esta)
        - Redirige a /admin/settings con mensaje de éxito
        ↓
[Cron]  email_reader_gmail.py (cada X segundos/minutos):
        - Lee de email_accounts la única cuenta con type='gmail'
        - Usa oauth_refresh_token para obtener access_token (Google OAuth2)
        - Gmail API: list messages (INBOX) → get cada mensaje (full)
        - Por cada mensaje: To/X-Original-To → recipient_email; filtra por asuntos (email_subjects); extrae códigos
        - Guarda en codes (recipient_email, origin='gmail', etc.)
        ↓
[Usuario final] En "Consulta tu código" escribe su correo → ve los códigos donde recipient_email = su correo
```

---

## 4. Dónde se gestiona la cuenta matriz

| Dónde | Qué se hace |
|-------|-------------|
| **Panel admin → Configuración** | Sección "Configurar cuenta Google (Gmail matriz)". Un solo botón: configurar o cambiar la cuenta. Solo puede haber una. |
| **Base de datos** | Tabla `email_accounts`: una fila con `type = 'gmail'`, `email` = cuenta matriz, `oauth_refresh_token` (y `oauth_token` opcional). El cron solo usa cuentas con `type = 'gmail'`. |
| **PHP** | `GmailController`: connect (OAuth) y callback (guardar tokens, borrar otras Gmail si vienes de Settings). `SettingsController` + vista: muestran la cuenta actual y el botón. `EmailAccountRepository`: getGmailMatrixAccount(), deleteOtherGmailAccountsExcept(). |
| **Python (cron)** | `cron/email_reader_gmail.py` → `EmailAccountRepository.find_by_type('gmail')` → para esa cuenta, `GmailService.read_account()` usa Gmail API y devuelve correos con `to_primary` ya resuelto desde headers. |

No hay “cuenta matriz” en otra tabla: la matriz es simplemente **la única cuenta Gmail** en `email_accounts`. Gestionarla es: en Configuración conectar/cambiar esa cuenta; el resto (tokens, lectura, filtros, guardado) ya está implementado.

---

## 5. Cómo se obtiene el destinatario real (correo matriz)

En `cron/gmail_service.py`, en `_parse_message()`:

1. Se leen los headers del mensaje: `To`, `X-Original-To`.
2. Si existe **X-Original-To** (típico en reenvíos), se usa como destinatario principal.
3. Si no, se usa el primer email del header **To**.
4. Si no hubiera ninguno, se usa el email de la cuenta que estamos leyendo.

Ese valor se guarda en el dict como `to_primary` y luego en BD como `recipient_email` del código. Así, aunque todos los correos lleguen al buzón de la matriz, cada código queda asociado al usuario correcto.

---

## 6. Resumen

- **API:** Gmail API (readonly). La que ya usamos está bien; no hace falta otra.
- **Gestión:** Solo desde **Configuración** en el panel: un solo botón para configurar o cambiar la cuenta Gmail matriz.
- **Flujo:** Configurar en panel → OAuth → se guarda una sola cuenta Gmail en `email_accounts` → el cron la lee con Gmail API, saca el destinatario de los headers y guarda códigos por `recipient_email`.
