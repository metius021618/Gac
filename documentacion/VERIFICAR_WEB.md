# ✅ Verificación Rápida - Acceso Web

## 🔍 Checklist para que se vea en la Web

### 1. ✅ Verificar Document Root en cPanel

1. Ir a **cPanel** → **Subdominios**
2. Buscar `gac.pocoyoni.com`
3. Verificar que el **Document Root** sea:
   ```
   /home/pocoavbb/gac.pocoyoni.com/public
   ```
4. Si no es así, **EDITAR** y cambiarlo a `/home/pocoavbb/gac.pocoyoni.com/public`
5. **Guardar cambios**

### 2. ✅ Verificar Archivos en el Servidor

Desde **File Manager** de cPanel, verificar que existan:

```
/home/pocoavbb/gac.pocoyoni.com/
├── .env                    ← Debe existir
├── vendor/                 ← Debe existir (carpeta completa)
├── public/
│   ├── index.php          ← Debe existir
│   └── .htaccess          ← Debe existir
└── src/
    └── ...
```

### 3. ✅ Verificar Base de Datos

Desde **phpMyAdmin**:

1. Seleccionar base de datos `pocoavbb_gac`
2. Verificar que existan estas tablas:
   - `platforms`
   - `settings`
   - `users`
   - `roles`
   - `email_accounts`
   - `codes`

Si no existen, ejecutar los scripts SQL:
- `database/schema.sql`
- `database/seed_platforms.sql`
- `database/seed_settings.sql`

### 4. ✅ Verificar Permisos

Desde **Terminal de cPanel**:

```bash
cd /home/pocoavbb/gac.pocoyoni.com
chmod 644 .env
chmod -R 755 public/
chmod -R 755 logs/
chmod -R 755 vendor/
```

### 5. ✅ Probar Acceso

Abrir en el navegador:
- **https://gac.pocoyoni.com**

**Si ves un error 404:**
- Verificar Document Root (paso 1)
- Verificar que `public/index.php` existe

**Si ves un error 500:**
- Revisar logs en `logs/` o en cPanel → Error Log
- Verificar que `.env` tenga las credenciales correctas
- Verificar que `vendor/autoload.php` existe

**Si ves un error de conexión a BD:**
- Verificar credenciales en `.env`
- Verificar que la base de datos existe y tiene las tablas

### 6. ✅ Verificar Logs de Errores

Desde **cPanel** → **Error Log** o desde Terminal:

```bash
tail -f /home/pocoavbb/gac.pocoyoni.com/logs/cron.log
```

O revisar el log de errores de PHP en cPanel.

---

## 🚨 Solución Rápida de Problemas

### Error 404 Not Found
```
Solución:
1. Verificar Document Root = /home/pocoavbb/gac.pocoyoni.com/public
2. Verificar que public/index.php existe
3. Verificar que public/.htaccess existe
```

### Error 500 Internal Server Error
```
Solución:
1. Revisar Error Log en cPanel
2. Verificar que .env existe y tiene credenciales correctas
3. Verificar que vendor/autoload.php existe (ejecutar: composer install)
4. Verificar permisos: chmod 644 .env
```

### Error: "Class not found" o "vendor/autoload.php not found"
```
Solución:
1. Desde Terminal: cd /home/pocoavbb/gac.pocoyoni.com
2. Ejecutar: composer install --no-dev
3. Verificar que vendor/ existe
```

### Error: "Database connection failed"
```
Solución:
1. Verificar .env tiene:
   DB_NAME=pocoavbb_gac
   DB_USER=pocoavbb_codeadd
   DB_PASSWORD=codepen@17
2. Verificar que la BD existe en cPanel → MySQL Databases
3. Verificar que el usuario tiene permisos
```

---

## ✅ Si Todo Está Correcto

Deberías ver:
- **Página de consulta de códigos** al acceder a `https://gac.pocoyoni.com`
- **Sin errores** en la consola del navegador
- **Sin errores** en los logs del servidor

---

## 📞 Próximos Pasos

Una vez que la página se vea:
1. Probar la consulta de códigos
2. Probar el login (si tienes usuario)
3. Configurar cron jobs para lectura automática de emails
