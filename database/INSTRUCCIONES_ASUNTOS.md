# 📧 Instrucciones: Tabla de Asuntos de Email

## 📋 ¿Dónde están los asuntos?

Los asuntos de email se almacenan en la tabla **`email_subjects`** en la base de datos **`pocoavbb_gac`**.

## 🗄️ Estructura de la Tabla

```sql
email_subjects
├── id (INT, PRIMARY KEY, AUTO_INCREMENT)
├── platform_id (INT, FOREIGN KEY → platforms.id)
├── subject_line (VARCHAR(500)) - El asunto del correo
├── category (VARCHAR(32)) - general | modo_hogar | modo_viaje
├── active (TINYINT(1)) - 1 = activo, 0 = eliminado (soft delete)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

## 🔍 Consulta que usa el sistema

El repositorio `EmailSubjectRepository` consulta así:

```sql
SELECT 
    es.id,
    es.platform_id,
    es.subject_line,
    es.active,
    es.created_at,
    es.updated_at,
    p.name as platform_name,
    p.display_name as platform_display_name
FROM email_subjects es
JOIN platforms p ON es.platform_id = p.id
WHERE es.active = 1
ORDER BY es.id DESC
```

## ⚠️ IMPORTANTE: La tabla NO está en schema.sql

La tabla `email_subjects` **NO está incluida** en el archivo `schema.sql` principal. 

### Para crear la tabla:

Ejecuta este script:
```bash
gac/SISTEMA_GAC/database/migrations/create_email_subjects_table.sql
```

### Para migrar datos desde la BD antigua:

Si tienes datos en la BD antigua (`pocoavbb_codes544shd.email_subjects`), ejecuta:
```bash
gac/SISTEMA_GAC/database/migrations/migrate_email_subjects_from_old_db.sql
```

Este script:
1. Crea la tabla si no existe
2. Migra los datos desde la BD antigua
3. Convierte `platform` (VARCHAR) → `platform_id` (INT)
4. Mapea nombres de plataformas a IDs

## ✅ Verificar si la tabla existe

Ejecuta en MySQL:

```sql
USE pocoavbb_gac;

-- Verificar si existe
SHOW TABLES LIKE 'email_subjects';

-- Ver estructura
DESCRIBE email_subjects;

-- Contar registros
SELECT COUNT(*) as total FROM email_subjects WHERE active = 1;
```

## 🚨 Si no hay datos

Si la tabla está vacía, puedes:

1. **Migrar desde BD antigua** (si existe):
   ```sql
   -- Ejecutar: migrate_email_subjects_from_old_db.sql
   ```

2. **Agregar manualmente desde la interfaz**:
   - Ir a `/admin/email-subjects`
   - Clic en "Nuevo asunto"
   - Completar el formulario

3. **Insertar directamente en SQL**:
   ```sql
   INSERT INTO email_subjects (platform_id, subject_line, active)
   VALUES 
       (1, 'Tu código de acceso temporal de Netflix', 1),
       (2, 'Completa tu suscripción a Disney+', 1);
   -- (Reemplaza los IDs con los IDs reales de tus plataformas)
   ```

## 📝 Nota sobre la BD antigua

En la BD antigua (`pocoavbb_codes544shd`), los asuntos estaban en:
- Tabla: `email_subjects`
- Campo: `platform` (VARCHAR) - Ej: "Netflix", "Disney"
- Campo: `subject_line` (VARCHAR)
- Campo: `active` (TINYINT)

En la nueva BD (`pocoavbb_gac`):
- Tabla: `email_subjects`
- Campo: `platform_id` (INT) - Referencia a `platforms.id`
- Campo: `subject_line` (VARCHAR(500))
- Campo: `active` (TINYINT)
