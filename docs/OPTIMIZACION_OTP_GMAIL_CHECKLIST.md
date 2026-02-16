# Checklist — Optimización OTP vía Gmail (event-driven)

Seguir en orden. Cada ítem es una acción concreta.  
*(Implementación de referencia ya hecha en el repo; marcar según lo que apliques en tu entorno.)*

---

## Base de datos

- [ ] **DB.1** Ejecutar migración: `database/migrate_optimizacion_otp_gmail.sql` (o aplicar los `ALTER TABLE` y `CREATE INDEX` del archivo).
- [ ] **DB.2** Confirmar que existe registro para `gmail_last_history_id` en `settings` (o crear con INSERT del mismo archivo). Si tu tabla usa `key` en lugar de `name`, usar la línea comentada del SQL.
- [ ] **DB.3** Leer `database/OPTIMIZACION_OTP_GMAIL_BD.md` para lógica de inserción (UPDATE is_current + INSERT) y consulta cliente (SELECT con is_current = 1).

---

## Gmail Watch (users.watch + renovación)

- [ ] **1.1** Configurar en `.env`: `GMAIL_PUBSUB_TOPIC=projects/TU_PROYECTO/topics/TU_TOPIC` (topic Pub/Sub con permisos para `gmail-api-push@system.gserviceaccount.com`).
- [ ] **1.2** Ejecutar una vez (o desde cron diario): `python cron/renew_gmail_watch.py` — registra el watch y guarda `gmail_last_history_id` y `gmail_watch_expiration` en `settings`.
- [ ] **1.3** Programar cron diario: `0 3 * * * cd /ruta/SISTEMA_GAC && python3 cron/renew_gmail_watch.py` para renovar el watch antes de que expire (~7 días).

---

## Webhook receiver

- [ ] **2.1** Usar `cron/gmail_webhook_receiver.py`: recibe POST de Pub/Sub, decodifica `message.data` (base64url) y extrae `historyId`.
- [ ] **2.2** Configurar la URL de push de la suscripción Pub/Sub a `https://tu-dominio/gmail/push` (o el puerto que uses). Arrancar con `python cron/gmail_webhook_receiver.py` o `flask --app cron.gmail_webhook_receiver run --host 0.0.0.0 --port 5050`. El webhook solo dispara `process_gmail_history.py`; no parsea correos ni escribe en BD.

---

## Worker (history + metadata + full solo si match)

- [ ] **3.1** El worker `cron/process_gmail_history.py` se invoca con `--history-id NUEVO`. Lee `gmail_last_history_id` de BD, llama `history.list(startHistoryId=anterior)` y obtiene IDs de mensajes nuevos.
- [ ] **3.2** Para cada ID: `messages.get(format="metadata")` (subject, from, to, date) — ver `gmail_service.get_message_metadata`.
- [ ] **3.3** Filtro por asunto con `EmailFilterService.filter_by_subject()`; si no hay match, no se descarga el cuerpo.
- [ ] **3.4** Solo si hay match: `messages.get(format="full")`, extraer datos y guardar con `CodeRepository.save_otp_current()` (UPDATE is_current=0 + INSERT is_current=1).
- [ ] **3.5** Al finalizar, guardar en `settings` el `gmail_last_history_id` con el valor recibido del webhook (nuevo historyId).

---

## Lógica is_current (inserción y consulta cliente)

- [ ] **4.1** Python: al guardar OTP desde el worker se usa `CodeRepository.save_otp_current()` (hace UPDATE is_current=0 y INSERT con is_current=1, email_date, gmail_message_id).
- [ ] **4.2** PHP: `CodeRepository::findLastEmail()` ya ordena por `(COALESCE(c.is_current, 0) = 1) DESC, c.received_at DESC` para preferir el OTP actual por (plataforma, destinatario).

---

## Dejar de hacer polling Gmail constante

- [ ] **5.1** En `.env` poner `CRON_GMAIL_EVENT_DRIVEN=true` para que `sync_loop.py` no ejecute el lector Gmail (solo IMAP y Outlook en el bucle).
- [ ] **5.2** El flujo Gmail queda: evento Pub/Sub → webhook → `process_gmail_history.py` (history.list + metadata + full solo si match).

---

## Riesgos y mitigaciones (recomendación senior)

- **Refresher:** `renew_gmail_watch.py` loguea la **expiration** recibida (fecha legible) y hace **ALERT** + exit(1) si falla la renovación, para que cron pueda enviar mail. Si falla 2–3 días, el sistema deja de recibir OTP; configurar cron con salida a mail.
- **Event bursts:** Si llegan muchos correos, el webhook puede disparar múltiples workers. **Mitigación actual:** lock file (`logs/gmail_worker.lock`): solo un worker a la vez; si otro está en curso se omite el push y el siguiente trae el historyId más reciente. Solución futura: cola interna (Redis o DB).
- **Duplication safe:** Si Pub/Sub reenvía el mismo evento, es seguro gracias a **gmail_message_id UNIQUE** en `codes` (el INSERT falla por duplicado). ✔ Ya cubierto.
- **Watch expiration silencioso:** Monitor `cron/check_gmail_watch_health.py`: verifica si no llegan eventos en X horas (`CRON_GMAIL_NO_EVENT_ALERT_HOURS`, default 24) y si la expiración del watch está próxima o pasada. Ejecutar desde cron cada 6 h; exit(1) en alerta para poder enviar mail.

---

## Verificación final

- [ ] **V.1** Probar que un correo OTP que cumple el filtro de asunto llega al panel y se muestra como “último código” (is_current = 1).
- [ ] **V.2** Probar que correos que no cumplen el asunto no descargan cuerpo (menos uso de API).
- [ ] **V.3** Comprobar que no aparecen 429 (rateLimitExceeded) en uso normal tras el cambio.

Cuando todo esté marcado, la optimización OTP vía Gmail en modo event-driven estará aplicada.
