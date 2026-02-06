# 🤖 Cron Jobs - GAC

Scripts Python para automatizar la lectura de emails y extracción de códigos.

---

## 📋 Requisitos

- Python 3.9 o superior
- MySQL 8.0 o superior
- Acceso IMAP a cuentas de email configuradas

---

## 🔧 Instalación

### 1. Instalar dependencias

```bash
cd SISTEMA_GAC/cron
pip install -r requirements.txt
```

O con Python 3 específico:

```bash
python3 -m pip install -r requirements.txt
```

### 2. Configurar variables de entorno

Asegúrate de que el archivo `.env` en la raíz del proyecto tenga todas las variables necesarias:

```env
# Base de Datos Operativa
DB_HOST=localhost
DB_PORT=3306
DB_NAME=gac_operational
DB_USER=root
DB_PASSWORD=tu_password

# Base de Datos Warehouse
WAREHOUSE_DB_HOST=localhost
WAREHOUSE_DB_PORT=3306
WAREHOUSE_DB_NAME=gac_warehouse
WAREHOUSE_DB_USER=root
WAREHOUSE_DB_PASSWORD=tu_password

# Configuración de Cron
CRON_ENABLED=true
CRON_EMAIL_READER_INTERVAL=5
CRON_WAREHOUSE_SYNC_INTERVAL=60

# Logging
LOG_LEVEL=info
```

### 3. Crear directorio de logs

```bash
mkdir -p ../logs
chmod 755 ../logs
```

---

## 🚀 Uso

### Ejecución Manual

```bash
python3 email_reader.py
```

O desde la raíz del proyecto:

```bash
python3 cron/email_reader.py
```

### Ejecución con Logs

```bash
python3 email_reader.py 2>&1 | tee -a ../logs/cron.log
```

### Un solo cron: nuevo correo + relleno de cuerpos

El script **email_reader.py** hace todo en una ejecución:

1. **Nuevos correos:** Lee la cuenta maestra (ej. streaming@pocoyoni.com), filtra por asunto o por remitente (DE: Disney+, Netflix, etc.), extrae códigos y los guarda en la BD con el **destinatario real** (cabeceras Delivered-To / X-Original-To) y el cuerpo del email.
2. **Cuerpos antiguos:** Con la misma lectura de correo, actualiza en la BD los registros en `codes` que tengan `email_body` vacío (matching por asunto, remitente y destinatario).

No hace falta ejecutar otro script. Si en "Consulta tu Código" ves "El contenido del email no está disponible", basta con que el cron siga corriendo; en las siguientes ejecuciones se rellenará el cuerpo.

Opcional: si quieres rellenar muchos cuerpos de una vez sin esperar al cron, puedes ejecutar manualmente:

```bash
cd SISTEMA_GAC/cron
python3 update_old_emails_body.py
```

---

## ⚡ Sincronización cada 30 segundos (consultar código al instante)

Para que el botón **Consultar código** responda al instante (sin esperar 1–2 minutos), los lectores de correo deben estar actualizando la BD continuamente. Usa el script **sync_loop.py**, que ejecuta los 3 lectores (Pocoyoni/IMAP, Gmail, Outlook) en paralelo cada **30 segundos**.

### Ejecución manual (desde la raíz SISTEMA_GAC)

```bash
cd /ruta/a/SISTEMA_GAC
python cron/sync_loop.py
```

### Dejar corriendo en segundo plano

**Linux / Mac:**

```bash
cd /ruta/a/SISTEMA_GAC
mkdir -p logs
nohup python3 cron/sync_loop.py >> logs/sync_loop.log 2>&1 &
```

**Windows (CMD o PowerShell):**

```cmd
cd C:\ruta\a\SISTEMA_GAC
if not exist logs mkdir logs
start /B python cron/sync_loop.py >> logs/sync_loop.log 2>&1
```

Con el loop corriendo, el botón **Consultar código** en la web solo consulta la BD (respuesta inmediata) y los datos tienen como máximo ~30 segundos de antigüedad.

---

## ⏰ Configurar Cron Jobs

### Linux / Unix

Editar crontab:

```bash
crontab -e
```

Agregar línea (ejecutar cada 5 minutos):

```cron
*/5 * * * * cd /ruta/completa/a/SISTEMA_GAC && /usr/bin/python3 cron/email_reader.py >> logs/cron.log 2>&1
```

O cada 10 minutos:

```cron
*/10 * * * * cd /ruta/completa/a/SISTEMA_GAC && /usr/bin/python3 cron/email_reader.py >> logs/cron.log 2>&1
```

### cPanel

1. Ir a **Cron Jobs** en cPanel
2. Agregar nuevo cron job:
   - **Minuto:** `*/5` (cada 5 minutos)
   - **Hora:** `*`
   - **Día:** `*`
   - **Mes:** `*`
   - **Día de la semana:** `*`
   - **Comando:** (usa la ruta que devuelve `which python3` en el servidor; suele ser `/bin/python3` o `/usr/bin/python3`)
     ```bash
     cd /home/pocoavbb/app.pocoyoni.com && /bin/python3 crony/email_reader.py >> logs/cron.log 2>&1
     ```

### Windows (Task Scheduler)

1. Abrir **Task Scheduler**
2. Crear tarea básica
3. Configurar:
   - **Trigger:** Repetir cada 5 minutos
   - **Action:** Iniciar programa
   - **Programa:** `python.exe`
   - **Argumentos:** `C:\ruta\a\SISTEMA_GAC\cron\email_reader.py`
   - **Directorio:** `C:\ruta\a\SISTEMA_GAC`

---

## 📊 Estructura de Archivos

```
cron/
├── README.md              # Esta documentación
├── requirements.txt        # Dependencias Python
├── config.py              # Configuración
├── database.py            # Conexión a BD
├── repositories.py        # Repositorios de datos
├── imap_service.py        # Servicio IMAP
├── email_filter.py        # Filtrado por asunto
├── code_extractor.py      # Extracción de códigos
└── email_reader.py        # Script principal
```

---

## 🔍 Flujo de Procesamiento (un solo cron)

1. **Cuenta maestra:** Usa la cuenta IMAP marcada como maestra (ej. streaming@pocoyoni.com).
2. **Lectura:** Lee los últimos emails del buzón (hasta 300).
3. **Filtrado:** Filtra por asunto o por remitente (DE: Disney+, Netflix, etc.) usando settings y mapeo DE → plataforma.
4. **Extracción:** Extrae códigos con regex por plataforma; el **destinatario real** se toma de Delivered-To / X-Original-To / X-Envelope-To.
5. **Guardado:** Guarda cada código en BD con `recipient_email` en minúsculas (el correo que consultará).
6. **Backfill:** Con la misma lectura, actualiza `email_body` en registros de `codes` que lo tengan vacío (matching por asunto, remitente y destinatario).
7. **Sincronización:** Actualiza estado de la cuenta.

---

## 📝 Logs

Los logs se guardan en `SISTEMA_GAC/logs/cron.log`

Ejemplo de log:

```
2024-01-15 10:30:00 - __main__ - INFO - ============================================================
2024-01-15 10:30:00 - __main__ - INFO - Iniciando lectura automática de emails
2024-01-15 10:30:00 - __main__ - INFO - ============================================================
2024-01-15 10:30:01 - __main__ - INFO - Procesando 1 cuenta(s) IMAP
2024-01-15 10:30:01 - __main__ - INFO - Procesando cuenta: cuenta@dominio.com (ID: 1)
2024-01-15 10:30:02 - __main__ - INFO -   - Emails leídos: 10
2024-01-15 10:30:02 - __main__ - INFO -   - Emails filtrados: 3
2024-01-15 10:30:02 - __main__ - INFO -   - Códigos extraídos: 2
2024-01-15 10:30:03 - __main__ - INFO -   - ✓ Código guardado: 123456 (netflix)
2024-01-15 10:30:03 - __main__ - INFO -   - ✓ Código guardado: 789012 (disney)
2024-01-15 10:30:03 - __main__ - INFO -   - Códigos guardados en esta cuenta: 2
2024-01-15 10:30:03 - __main__ - INFO - ============================================================
2024-01-15 10:30:03 - __main__ - INFO - Proceso completado. Total de códigos guardados: 2
2024-01-15 10:30:03 - __main__ - INFO - ============================================================
```

---

## ⚠️ Solución de Problemas

### Error: "No module named 'mysql.connector'"

```bash
pip install mysql-connector-python
```

### Error: "No module named 'dotenv'"

```bash
pip install python-dotenv
```

### Error: "Can't connect to MySQL server"

- Verificar credenciales en `.env`
- Verificar que MySQL esté corriendo
- Verificar firewall/permisos

### Error: "IMAP connection failed"

- Verificar configuración IMAP en `email_accounts`
- Verificar credenciales de email
- Verificar puerto y encriptación (SSL/TLS)

### El sistema no lee correos enviados a un destinatario (ej. casa2025@pocoyoni.com)

1. **Destinatario real:** Si todos los correos llegan a la cuenta maestra, el servidor puede reescribir el "To" a la cuenta maestra. El cron usa las cabeceras **Delivered-To**, **X-Original-To**, **X-Envelope-To** para obtener el destinatario real (el que consultará el código). Asegúrate de que tu servidor de correo añada una de estas cabeceras con la dirección original (ej. casa2025@pocoyoni.com).

2. **Asunto debe coincidir:** En "Asuntos de correo" debe haber al menos un asunto que coincida con el del email (ej. "Tu código de acceso único para Disney+" o "código de acceso único" para Disney+). Si no hay coincidencia, el email se descarta.

3. **Cuerpo con código:** El cuerpo del email debe contener un código numérico que coincida con la plataforma (ej. Disney 6–8 dígitos). Si no se extrae código, no se guarda.

4. **Cron ejecutado:** El cron debe estar programado y ejecutándose (cada 5–10 min). Tras enviar el correo, espera a que corra el cron.

### Logs no se crean

```bash
mkdir -p logs
chmod 755 logs
touch logs/cron.log
chmod 644 logs/cron.log
```

---

## 🔐 Seguridad

- **Credenciales:** Nunca commitees el archivo `.env`
- **Permisos:** Asegúrate de que los logs tengan permisos adecuados
- **Conexiones:** Usa SSL/TLS para IMAP y MySQL cuando sea posible

---

## 📚 Referencias

- Documentación PHP: `../documentacion/`
- Schema de BD: `../database/schema.sql`
- Configuración: `config.py`

---

**Última actualización:** 2024