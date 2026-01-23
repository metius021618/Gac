# 📋 Configuración del Archivo .env

## 📍 Ubicación

El archivo `.env` debe estar en la **raíz del proyecto**:

```
SISTEMA_GAC/
├── .env                    ← AQUÍ
├── .gitignore
├── composer.json
├── public/
├── src/
└── ...
```

## 🔐 Variables de Entorno Completas

### Configuración de la Aplicación

```env
APP_NAME=GAC
APP_VERSION=2.0.0
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gac.pocoyoni.com
```

**Notas:**
- `APP_ENV`: `production` para producción, `development` para desarrollo
- `APP_DEBUG`: `false` en producción, `true` en desarrollo
- `APP_URL`: URL completa de tu aplicación (con https://)

### Base de Datos Operativa

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=pocoavbb_gac
DB_USER=pocoavbb_codeadd
DB_PASSWORD=codepen@17
DB_CHARSET=utf8mb4
DB_COLLATE=utf8mb4_spanish_ci
```

**Valores para producción (cPanel):**
- `DB_HOST`: Generalmente `localhost` en cPanel
- `DB_NAME`: `pocoavbb_gac`
- `DB_USER`: `pocoavbb_codeadd`
- `DB_PASSWORD`: `codepen@17`

### Base de Datos Warehouse

```env
WAREHOUSE_DB_HOST=localhost
WAREHOUSE_DB_PORT=3306
WAREHOUSE_DB_NAME=pocoavbb_gac
WAREHOUSE_DB_USER=pocoavbb_codeadd
WAREHOUSE_DB_PASSWORD=codepen@17
```

**Nota:** Por ahora, usar la misma base de datos que la operativa.

### Seguridad

```env
APP_KEY=
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTPONLY=true
ENCRYPTION_KEY=
```

**Generar claves:**
```bash
# Generar APP_KEY (32 caracteres hexadecimales)
openssl rand -hex 32

# Generar ENCRYPTION_KEY (32 caracteres hexadecimales)
openssl rand -hex 32
```

**Notas:**
- `SESSION_SECURE`: `true` si usas HTTPS, `false` para desarrollo local
- `SESSION_LIFETIME`: Tiempo en minutos (120 = 2 horas)

### Gmail API (OAuth 2.0)

```env
GMAIL_CLIENT_ID=
GMAIL_CLIENT_SECRET=
GMAIL_REDIRECT_URI=https://gac.pocoyoni.com/auth/gmail/callback
GMAIL_SCOPES=https://www.googleapis.com/auth/gmail.readonly
```

**Obtener credenciales:**
1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un proyecto o selecciona uno existente
3. Habilita la API de Gmail
4. Crea credenciales OAuth 2.0
5. Agrega la URI de redirección autorizada

### IMAP (Valores por defecto)

```env
IMAP_HOST=
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=true
```

**Nota:** Estos valores son por defecto. Cada cuenta de email puede tener su propia configuración en la base de datos.

### Logging

```env
LOG_CHANNEL=file
LOG_LEVEL=info
```

**Niveles de log:**
- `debug`: Información detallada
- `info`: Información general
- `warning`: Advertencias
- `error`: Errores
- `critical`: Errores críticos

### Cron Jobs

```env
CRON_ENABLED=true
CRON_EMAIL_READER_INTERVAL=5
CRON_WAREHOUSE_SYNC_INTERVAL=60
```

**Notas:**
- `CRON_EMAIL_READER_INTERVAL`: Minutos entre lecturas de email (5 = cada 5 minutos)
- `CRON_WAREHOUSE_SYNC_INTERVAL`: Minutos entre sincronizaciones al warehouse (60 = cada hora)

### Rate Limiting

```env
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=60
```

**Nota:** Limita el número de solicitudes por minuto por IP.

### Zona Horaria y Locale

```env
TIMEZONE=America/Mexico_City
LOCALE=es_ES
FALLBACK_LOCALE=es_ES
```

## 📝 Ejemplo Completo para Producción

```env
# Aplicación
APP_NAME=GAC
APP_VERSION=2.0.0
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gac.pocoyoni.com

# Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=pocoavbb_gac
DB_USER=pocoavbb_codeadd
DB_PASSWORD=codepen@17
DB_CHARSET=utf8mb4
DB_COLLATE=utf8mb4_spanish_ci

WAREHOUSE_DB_HOST=localhost
WAREHOUSE_DB_PORT=3306
WAREHOUSE_DB_NAME=pocoavbb_gac
WAREHOUSE_DB_USER=pocoavbb_codeadd
WAREHOUSE_DB_PASSWORD=codepen@17

# Seguridad
APP_KEY=TU_CLAVE_AQUI_32_CARACTERES_HEX
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTPONLY=true
ENCRYPTION_KEY=TU_CLAVE_AQUI_32_CARACTERES_HEX

# Gmail API
GMAIL_CLIENT_ID=TU_CLIENT_ID
GMAIL_CLIENT_SECRET=TU_CLIENT_SECRET
GMAIL_REDIRECT_URI=https://gac.pocoyoni.com/auth/gmail/callback
GMAIL_SCOPES=https://www.googleapis.com/auth/gmail.readonly

# IMAP
IMAP_HOST=
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=true

# Logging
LOG_CHANNEL=file
LOG_LEVEL=info

# Cron
CRON_ENABLED=true
CRON_EMAIL_READER_INTERVAL=5
CRON_WAREHOUSE_SYNC_INTERVAL=60

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=60

# Zona Horaria
TIMEZONE=America/Mexico_City
LOCALE=es_ES
FALLBACK_LOCALE=es_ES
```

## ⚠️ Importante

1. **Nunca subas el archivo `.env` al repositorio** (está en `.gitignore`)
2. **Genera claves seguras** para `APP_KEY` y `ENCRYPTION_KEY`
3. **Usa HTTPS en producción** (`SESSION_SECURE=true`)
4. **Configura `APP_DEBUG=false` en producción**
5. **Verifica las credenciales de base de datos** antes de desplegar

## 🔧 Verificar Configuración

Para verificar que el archivo `.env` se está cargando correctamente:

```php
// En cualquier archivo PHP
var_dump($_ENV['DB_NAME']); // Debe mostrar: pocoavbb_gac
```

## 📚 Referencias

- [Documentación de phpdotenv](https://github.com/vlucas/phpdotenv)
- [Google Cloud Console](https://console.cloud.google.com/)
- [Lista de zonas horarias PHP](https://www.php.net/manual/es/timezones.php)
