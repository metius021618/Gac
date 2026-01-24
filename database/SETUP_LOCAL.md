# 🗄️ Configuración de Base de Datos Local

## 📋 Pasos para configurar la BD local

### 1. Crear la Base de Datos

Ejecuta los siguientes scripts SQL **en orden** usando phpMyAdmin o tu cliente MySQL:

#### Orden de ejecución:

1. **`schema_local.sql`** - Crea la BD `gac_local` y todas las tablas
2. **`seed_platforms_local.sql`** - Inserta las plataformas (Netflix, Disney+, etc.)
3. **`seed_settings_local.sql`** - Inserta los asuntos de email y configuraciones
4. **`seed_admin_user_local.sql`** - Inserta el usuario administrador

### 2. Configurar el archivo `.env`

Asegúrate de que tu archivo `.env` en la raíz del proyecto tenga:

```env
# Base de Datos Local
DB_HOST=localhost
DB_PORT=3306
DB_NAME=gac_local
DB_USER=root
DB_PASSWORD=tu_password_local

# Warehouse (usar la misma BD por ahora)
WAREHOUSE_DB_HOST=localhost
WAREHOUSE_DB_PORT=3306
WAREHOUSE_DB_NAME=gac_local
WAREHOUSE_DB_USER=root
WAREHOUSE_DB_PASSWORD=tu_password_local

# Aplicación
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8001
```

### 3. Credenciales por defecto

- **Usuario:** `admin`
- **Contraseña:** `admin123`

### 4. Verificar conexión

Después de ejecutar los scripts, recarga la página del login. Si todo está bien, deberías poder iniciar sesión.

## ⚠️ Notas

- Los scripts `*_local.sql` están configurados para usar la BD `gac_local`
- No afectan la BD de producción (`pocoavbb_gac`)
- Puedes ejecutar los scripts múltiples veces (usan `INSERT IGNORE`)
