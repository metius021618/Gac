# Base de datos — Optimización OTP vía Gmail (event-driven)

Este archivo es la **única referencia** para todos los cambios de BD de la optimización: nuevos campos, historial incremental y consulta del cliente. Ejecutar en el orden indicado.

---

## Resumen de cambios

| Qué | Dónde | Para qué |
|-----|--------|----------|
| Nuevos campos en `codes` | tabla `codes` | email_date, gmail_message_id, is_current, expires_at |
| Último historyId de Gmail | tabla `settings` | Lectura incremental con history.list (evitar polling) |
| Lógica de inserción | aplicación (Python/PHP) | Marcar anteriores is_current=0, insertar nuevo con is_current=1 |
| Consulta del panel cliente | aplicación | Filtrar por is_current=1 para mostrar solo el OTP más reciente |

---

## 1. Añadir columnas a `codes`

Ejecutar en tu base de datos (ajusta `USE` si usas otro nombre):

```sql
-- ============================================
-- 1) NUEVOS CAMPOS EN CODES (optimización OTP Gmail)
-- ============================================
-- email_date: fecha real del correo (para orden/consulta).
-- gmail_message_id: ID del mensaje en Gmail (evitar duplicados, UNIQUE).
-- is_current: solo un código "actual" por (email_account_id, platform_id); el cliente consulta is_current=1.
-- expires_at: opcional, para códigos con caducidad.

-- Si tu BD tiene nombre fijo:
-- USE pocoavbb_gac;

ALTER TABLE codes
  ADD COLUMN IF NOT EXISTS email_date DATETIME NULL COMMENT 'Fecha del correo (desde header o internalDate)' AFTER received_at,
  ADD COLUMN IF NOT EXISTS gmail_message_id VARCHAR(255) NULL COMMENT 'ID mensaje Gmail (evita duplicados)' AFTER email_date,
  ADD COLUMN IF NOT EXISTS is_current TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=solo el último OTP por cuenta+plataforma' AFTER gmail_message_id,
  ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL COMMENT 'Caducidad opcional del código' AFTER is_current;

-- Índice para consulta del cliente (siempre último OTP por cuenta + plataforma)
CREATE UNIQUE INDEX IF NOT EXISTS idx_gmail_message_id ON codes (gmail_message_id);
CREATE INDEX IF NOT EXISTS idx_codes_current_lookup ON codes (email_account_id, platform_id, is_current);

-- Nota: Si tu MySQL no soporta IF NOT EXISTS en ALTER ADD COLUMN, usa solo:
-- ALTER TABLE codes
--   ADD COLUMN email_date DATETIME NULL AFTER received_at,
--   ADD COLUMN gmail_message_id VARCHAR(255) NULL AFTER email_date,
--   ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 1 AFTER gmail_message_id,
--   ADD COLUMN expires_at DATETIME NULL AFTER is_current;
-- (ejecutar una sola vez; si la columna ya existe, omitir esa línea o usar procedimiento con comprobación)
```

**Si tu versión de MySQL no admite `ADD COLUMN IF NOT EXISTS`**, ejecuta esto una sola vez (y omite las columnas que ya existan):

```sql
ALTER TABLE codes
  ADD COLUMN email_date DATETIME NULL AFTER received_at,
  ADD COLUMN gmail_message_id VARCHAR(255) NULL AFTER email_date,
  ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 1 AFTER gmail_message_id,
  ADD COLUMN expires_at DATETIME NULL AFTER is_current;

CREATE UNIQUE INDEX idx_gmail_message_id ON codes (gmail_message_id);
CREATE INDEX idx_codes_current_lookup ON codes (email_account_id, platform_id, is_current);
```

---

## 2. Guardar `gmail_last_history_id`

El sistema debe guardar el **historyId** que devuelve Gmail (p. ej. tras `history.list`) para la siguiente lectura incremental. Se usa la tabla **settings** (nombre = `gmail_last_history_id`).

No hace falta crear tabla nueva; `settings` ya existe. La aplicación hará:

- **Leer:** `SELECT value FROM settings WHERE name = 'gmail_last_history_id' LIMIT 1`
- **Escribir:**  
  `INSERT INTO settings (name, value, type) VALUES ('gmail_last_history_id', ?, 'string')`  
  `ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()`

Si en tu esquema `settings` no tiene `updated_at`, usa solo `UPDATE value = ?`.

**Opción:** Si prefieres un historyId por cuenta (varias cuentas Gmail en el futuro), puedes añadir una tabla:

```sql
-- Opcional: solo si quieres historyId por cuenta (varias Gmail)
CREATE TABLE IF NOT EXISTS gmail_watch_state (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email_account_id INT NOT NULL UNIQUE,
  history_id VARCHAR(50) NOT NULL,
  expiration_ts BIGINT NULL COMMENT 'Unix ms cuando expira el watch',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (email_account_id) REFERENCES email_accounts(id) ON DELETE CASCADE
);
```

Para **una sola cuenta matriz**, basta con `settings` y el nombre `gmail_last_history_id`.

---

## 3. Lógica de inserción (OTP nuevo)

Cuando entra un **OTP nuevo** (tras filtro por asunto y descarga full), la aplicación debe:

1. **Marcar como no actuales** los códigos previos de ese (email_account_id, platform_id):

```sql
UPDATE codes
SET is_current = 0
WHERE email_account_id = ?
  AND platform_id = ?
  AND (is_current = 1 OR is_current IS NULL);
```

2. **Insertar el nuevo** con `is_current = 1` (y el resto de campos: code, subject, recipient_email, gmail_message_id, email_date, etc.):

```sql
INSERT INTO codes (
  email_account_id, platform_id, code, email_from, subject, email_body,
  received_at, email_date, gmail_message_id, is_current, expires_at,
  origin, status, recipient_email
) VALUES (
  ?, ?, ?, ?, ?, ?,
  ?, ?, ?, 1, ?,
  'gmail', 'available', ?
);
```

- Para Gmail: rellenar `gmail_message_id` y `email_date` cuando existan.
- `received_at` puede ser la misma fecha que `email_date` o la de procesamiento; `email_date` = fecha del correo.

---

## 4. Consulta del panel cliente (siempre el OTP más reciente)

Para mostrar **solo el último código válido** por cuenta y plataforma (evitar race conditions y múltiples códigos):

```sql
SELECT *
FROM codes
WHERE email_account_id = ?
  AND platform_id = ?
  AND is_current = 1
LIMIT 1;
```

- En la app (PHP/JS) se usan los `email_account_id` y `platform_id` del usuario/cuenta actual.
- Así el cliente siempre ve el OTP más reciente para esa combinación.

---

## 5. Compatibilidad con datos antiguos

Si ya tienes filas en `codes` sin `is_current`:

- Tras añadir la columna con `DEFAULT 1`, todas quedarán con `is_current = 1`.
- Para dejar **solo uno** por (email_account_id, platform_id) como “actual”, puedes normalizar (opcional):

```sql
-- Opcional: dejar solo el registro más reciente por (email_account_id, platform_id) como is_current=1
UPDATE codes c
JOIN (
  SELECT id
  FROM (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY email_account_id, platform_id ORDER BY received_at DESC, id DESC) AS rn
    FROM codes
  ) t
  WHERE rn = 1
) latest ON c.id = latest.id
SET c.is_current = 1;

UPDATE codes
SET is_current = 0
WHERE (email_account_id, platform_id, id) NOT IN (
  SELECT email_account_id, platform_id, id FROM (
    SELECT email_account_id, platform_id, id,
           ROW_NUMBER() OVER (PARTITION BY email_account_id, platform_id ORDER BY received_at DESC, id DESC) AS rn
    FROM codes
  ) t
  WHERE rn = 1
);
```

(En MySQL antiguo sin `ROW_NUMBER` se puede hacer con variables o un script en la aplicación.)

---

## Orden recomendado de ejecución

1. Ejecutar el **ALTER TABLE** y los **CREATE INDEX** de la sección 1 (una sola vez).
2. (Opcional) Crear **gmail_watch_state** si quieres historyId por cuenta; si no, usar solo **settings** para `gmail_last_history_id`.
3. (Opcional) Ejecutar la normalización de **is_current** de la sección 5 si ya había datos.
4. En la aplicación: implementar la **inserción** con UPDATE + INSERT (sección 3) y la **consulta** con `is_current = 1` (sección 4).

Con esto la BD queda lista para la optimización OTP vía Gmail (event-driven, historyId incremental y “solo último código” por cuenta y plataforma).
