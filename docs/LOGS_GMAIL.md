# Logs solo Gmail – cómo se procesa lo que recibe

Todo lo que el sistema hace con Gmail (recibir, leer, desmenuzar y guardar) se puede seguir en estos archivos.

---

## Dónde están los logs de Gmail

Dentro del proyecto **SISTEMA_GAC** (en el servidor suele ser algo como `~/app.pocoyoni.com` o `ruta/SISTEMA_GAC`):

| Archivo | Qué registra |
|--------|----------------|
| **logs/gmail_webhook.log** | Cada vez que Google avisa de correos nuevos (push). Una línea por evento: `push historyId=...` |
| **logs/gmail_push_worker.log** | Lo que hace el worker al procesar ese evento: mensajes leídos, asunto, destinatario, qué se guarda. **Aquí ves el “desmenuzado”.** |
| **logs/cron.log** | Si usas el lector por tiempo (polling) en vez de por evento, aquí se escribe lo mismo que el worker: cuenta, correos leídos, asunto → destinatario, filtrados, guardados. |

En **Windows** (desarrollo), la ruta sería por ejemplo:
`C:\Users\MATHIAS\Desktop\STREAMIN\gac\SISTEMA_GAC\logs\`

---

## Ver en vivo (servidor Linux)

```bash
cd /ruta/a/SISTEMA_GAC

# 1) Llegan notificaciones de Gmail
tail -f logs/gmail_webhook.log

# 2) Cómo se procesa cada mensaje (lee, desmenuza, guarda)
tail -f logs/gmail_push_worker.log
```

Si en tu instalación Gmail se lee por **cron** (polling) en vez de por webhook:

```bash
tail -f logs/cron.log
```

Ahí verás solo las líneas del lector Gmail (las de “Procesando cuenta Gmail”, “Emails leídos”, “Recibido”, “Correo guardado”, etc.).

---

## Qué significa cada cosa (cómo se lee y desmenuza)

### 1. Entrada del evento (gmail_webhook.log)

- `push historyId=123456789`  
  Google notificó “hay cambios desde este historyId”. El PHP recibe el push y lanza el worker con ese `historyId`.

### 2. Procesamiento (gmail_push_worker.log o cron.log)

- **No hay gmail_last_history_id en BD**  
  Primera vez; solo se guarda el historyId para el próximo push.

- **history.list no devolvió mensajes nuevos**  
  El evento no trajo IDs de mensajes nuevos (o ya se habían procesado). Se actualiza el historyId y se termina.

- **`[msg <id>] asunto='...' → destinatario=...`**  
  Para cada mensaje nuevo: **lectura de metadata** (asunto y destinatario `to_primary`). Es el “desmenuzado” del mensaje: qué asunto tiene y a qué correo va (por headers To / X-Original-To o, en reenvíos, por el “To:” del cuerpo).

- **OTP guardado: msg_id=... -> correo@ejemplo.com**  
  Ese mensaje pasó el filtro de asuntos, se leyó completo, se extrajo destinatario y se guardó en BD asociado a ese correo (el que luego se usa en “Consulta tu código”).

- **Procesados N mensajes, guardados M. historyId actualizado.**  
  Resumen del ciclo: cuántos mensajes se miraron y cuántos se guardaron; se actualiza el historyId para el siguiente push.

### 3. Si usas polling (cron.log – lector Gmail)

- **Procesando cuenta Gmail: cuenta@gmail.com (ID: ...)**  
  Cuenta que se está leyendo.

- **Emails leídos: N**  
  Cuántos mensajes devolvió la Gmail API para este ciclo.

- **Recibido[i]: asunto='...' → destinatario=...**  
  Cada mensaje leído: asunto (primeros ~70 caracteres) y destinatario (`to_primary`). Aquí ves el “desmenuzado” por mensaje.

- **Emails filtrados: N**  
  Cuántos pasaron el filtro de “Asuntos de correo” (tabla en admin). Si aquí sale 0 y “Emails leídos” > 0, el asunto no está en la BD o no coincide.

- **Asuntos no coinciden con BD**  
  Lista de asuntos que no hicieron match; puedes añadirlos en Admin → Asuntos de correo.

- **✓ Correo guardado: DE=... → destinatario**  
  Ese correo se guardó en `codes` con ese remitente y ese destinatario (para la consulta por correo).

- **Ya existía en BD (no se guarda de nuevo)**  
  Mismo asunto/remitente/destinatario/fecha ya registrado; no se duplica.

---

## Resumen del flujo (qué log mirar)

1. **“¿Me está llegando el aviso de Gmail?”** → `logs/gmail_webhook.log`
2. **“¿Qué mensajes lee y cómo los desmenuza (asunto, destinatario)?”** → `logs/gmail_push_worker.log` (o `logs/cron.log` si usas polling)
3. **“¿En qué parte falla o hay conflicto?”** → Mismo archivo: si ves el `[msg ...]` pero no el “OTP guardado”, el mensaje no pasó filtro de asunto o de plataforma; si no ves ningún `[msg ...]`, no hubo mensajes nuevos o falló antes (historyId, cuenta, etc.).

---

## Errores frecuentes y qué hacer

### Read timed out / Remote end closed connection (oauth2.googleapis.com)

El servidor no pudo conectar o tardó más de 120 s en responder al refresco del token OAuth. Suele ser red lenta, firewall o cortes puntuales.

- **Qué hace el sistema:** Se hace hasta **3 intentos** con 10 segundos entre ellos. Si tras eso sigue fallando, el worker termina con error; el siguiente push lo intentará de nuevo.
- **Qué revisar:** Que el servidor pueda hacer HTTPS a `oauth2.googleapis.com` (puerto 443). Si el hosting restringe salida, pedir que permitan ese dominio.

### Procesados N mensajes, guardados 0

Los mensajes se leen pero no se guardan. En el log deberías ver por cada mensaje una de estas líneas:

- **`saltado: asunto no coincide con Asuntos de correo (Admin)`** → El asunto no está en la tabla “Asuntos de correo”. Añádelo en Admin → Asuntos de correo.
- **`saltado: sin plataforma para este asunto`** → El asunto no tiene plataforma asociada.
- **`saltado: plataforma X no existe o esta deshabilitada`** → Activa la plataforma en Admin → Plataformas.

Si no aparece “saltado” y tampoco “OTP guardado”, ese mensaje ya estaba en BD (no se duplica).

### Las notificaciones (push) dejaron de llegar a una hora concreta

Si en **gmail_webhook.log** el último aviso es, por ejemplo, a las 7:12 y después no vuelve a aparecer nada, la causa habitual es que **el Gmail Watch caducó**.

- **Qué es el Watch:** Al usar Gmail “por eventos”, el sistema le pide a Google que avise (vía Pub/Sub) cuando lleguen correos. Ese “aviso” se registra con **users.watch** y tiene una **fecha de caducidad** (Google suele dar ~7 días).
- **Qué pasa al caducar:** Cuando llega esa fecha/hora, Google **deja de enviar pushes**. No es un fallo tuyo ni del servidor: simplemente el Watch ya no es válido.
- **Por qué coincide con una hora:** La caducidad es un instante exacto (p. ej. 7:12 UTC). A partir de ahí ya no se envía ningún push hasta que vuelvas a registrar un Watch.

**Qué hacer:**

1. **Recuperar los avisos ya:** En el servidor, ejecuta el script de renovación del Watch:
   ```bash
   cd /ruta/a/SISTEMA_GAC
   python3 cron/renew_gmail_watch.py
   ```
   Eso vuelve a registrar el Watch; en unos minutos Google debería enviar de nuevo los pushes a tu webhook.

2. **Evitar que vuelva a pasar:** Configura un **cron diario** que renueve el Watch antes de que caduque (por ejemplo a las 3:00):
   ```bash
   0 3 * * * cd /ruta/a/SISTEMA_GAC && python3 cron/renew_gmail_watch.py >> logs/renew_gmail_watch.log 2>&1
   ```
   Así el Watch se renueva cada día y no llegará a la fecha de caducidad.

3. **Comprobar si era caducidad:** Puedes revisar `logs/gmail_watch_health.log` (si tienes configurado `check_gmail_watch_health.py` cada 6 h). Si el Watch estaba expirado, ahí habrá una línea tipo:  
   `ALERT: Gmail Watch EXPIRADO desde X h (expiración: 2026-02-23 07:12:00 UTC). Ejecutar renew_gmail_watch.py.`

### El correo llega a Gmail pero no aparece "push historyId" en gmail_webhook.log

Si envías un correo a la cuenta matriz y en el log no sale ninguna línea nueva, **Google no está llegando a tu webhook** (o no está enviando el push). Posibles causas:

1. **URL del webhook incorrecta en Pub/Sub**  
   En Google Cloud Console → Pub/Sub → Subscriptions → tu suscripción push, el "Endpoint URL" debe ser exactamente la URL pública de tu script, por ejemplo:  
   `https://app.pocoyoni.com/gmail/push`  
   (con `https`, sin barra final o según cómo esté configurado tu servidor). Si está mal (dominio viejo, http en vez de https, typo), los pushes no llegarán.

2. **El servidor no recibe la petición**  
   Firewall, seguridad del hosting o reglas que bloqueen peticiones POST desde IPs de Google. Prueba desde fuera que el endpoint responda:  
   `curl -X POST https://app.pocoyoni.com/gmail/push -d '{}'`  
   Deberías recibir `ok` y en `gmail_webhook.log` una línea tipo `POST received (no historyId in body)`. Si no aparece esa línea al hacer la prueba, el problema es la ruta o el servidor; si sí aparece con curl pero no cuando llega el correo, el problema es Pub/Sub o la suscripción.

3. **Suscripción pausada o con errores**  
   En la misma suscripción en Cloud Console revisa si está pausada o si hay métricas de mensajes no entregados / dead letter.

4. **Desde el cambio reciente:** El webhook escribe **siempre** una línea por cada POST recibido:  
   - `POST received historyId=...` → push correcto, worker lanzado.  
   - `POST received (no historyId in body)` → llegó un POST pero el cuerpo no traía historyId (formato distinto o prueba manual).  
   Si tras enviar un correo **no** aparece ninguna línea nueva, la petición de Google no está llegando a tu PHP (revisar 1 y 2).
