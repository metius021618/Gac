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

### Rellenar cuerpo de correos antiguos (email_body vacío)

Si en "Consulta tu Código" ves "El contenido del email no está disponible", es porque ese correo se guardó sin cuerpo. Para rellenar el cuerpo de los correos ya guardados (leyendo desde IMAP):

```bash
cd SISTEMA_GAC/cron
python3 update_old_emails_body.py
```

Opcional: límite de emails a procesar (por defecto 500):

```bash
python3 update_old_emails_body.py 100
```

El script usa la cuenta maestra IMAP, lee los emails del servidor y actualiza en la BD los registros en `codes` que tengan `email_body` vacío (matching por asunto, remitente y destinatario). Log en `cron/logs/update_old_emails.log`.

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
   - **Comando:**
     ```bash
     cd /home/usuario/public_html/SISTEMA_GAC && /usr/bin/python3 cron/email_reader.py >> logs/cron.log 2>&1
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

## 🔍 Flujo de Procesamiento

1. **Lectura de Cuentas:** Obtiene **todas** las cuentas IMAP habilitadas (no solo la maestra)
2. **Lectura de Emails:** Lee el INBOX de **cada** cuenta (casa2025, streaming, etc.)
3. **Filtrado:** Filtra emails por asunto usando patrones de settings
4. **Extracción:** Extrae códigos usando regex por plataforma
5. **Validación:** Verifica duplicados y plataformas habilitadas
6. **Guardado:** Guarda códigos en BD operativa y warehouse
7. **Actualización:** Actualiza estado de sincronización de cuentas

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

### El sistema no lee correos enviados a casa2025 (u otro usuario)

El cron lee **todas** las cuentas IMAP habilitadas en "Correos Registrados". Para que lea los correos enviados a `casa2025@pocoyoni.com`:

1. **casa2025 debe estar en Correos Registrados** (tabla `email_accounts`), con `enabled = 1`.
2. **Credenciales IMAP correctas** para el buzón de casa2025: en `provider_config` debe ir el usuario y contraseña con los que se entra a ese correo (p. ej. casa2025 o LENINPERU y su contraseña).
3. **Cron en ejecución**: el cron debe estar programado (cada 5–10 min) en el servidor.
4. **Asunto del correo**: debe coincidir con un asunto registrado en "Asuntos de correo" (p. ej. Disney: "Tu código de acceso único para Disney+").

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