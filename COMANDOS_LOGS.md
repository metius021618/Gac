# Comandos para Ver Logs de PHP

## Windows (Local - XAMPP/WAMP)

### Ver logs de PHP en tiempo real:
```powershell
# Logs de PHP (XAMPP)
Get-Content C:\xampp\php\logs\php_error_log -Wait -Tail 50

# Logs de Apache (XAMPP)
Get-Content C:\xampp\apache\logs\error.log -Wait -Tail 50

# Logs de PHP (WAMP)
Get-Content C:\wamp64\logs\php_error.log -Wait -Tail 50

# Logs de Apache (WAMP)
Get-Content C:\wamp64\logs\apache_error.log -Wait -Tail 50
```

### Ver últimas líneas:
```powershell
# Últimas 50 líneas de PHP
Get-Content C:\xampp\php\logs\php_error_log -Tail 50

# Últimas 100 líneas
Get-Content C:\xampp\php\logs\php_error_log -Tail 100
```

### Buscar errores específicos:
```powershell
# Buscar "500" en los logs
Select-String -Path C:\xampp\php\logs\php_error_log -Pattern "500" -Context 5

# Buscar "Fatal error"
Select-String -Path C:\xampp\php\logs\php_error_log -Pattern "Fatal error" -Context 5
```

---

## Linux (Producción - cPanel/SSH)

### Ver logs de PHP en tiempo real:
```bash
# Log de PHP general
tail -f /usr/local/apache/logs/error_log

# Log de PHP específico del usuario (cPanel)
tail -f ~/public_html/gac/logs/php_error.log

# Log de PHP del dominio
tail -f ~/logs/error_log

# Últimas 50 líneas y seguir
tail -n 50 -f ~/logs/error_log
```

### Ver últimas líneas:
```bash
# Últimas 50 líneas
tail -n 50 ~/logs/error_log

# Últimas 100 líneas
tail -n 100 ~/logs/error_log

# Ver todo el archivo (paginado)
less ~/logs/error_log
```

### Buscar errores específicos:
```bash
# Buscar "500" en los logs
grep -n "500" ~/logs/error_log | tail -20

# Buscar "Fatal error" con contexto
grep -A 5 -B 5 "Fatal error" ~/logs/error_log | tail -30

# Buscar errores de hoy
grep "$(date +%Y-%m-%d)" ~/logs/error_log | tail -50

# Buscar errores relacionados con login
grep -i "login\|AuthController\|session" ~/logs/error_log | tail -30
```

### Ver logs de Apache/Nginx:
```bash
# Apache error log
tail -f /var/log/apache2/error.log

# Nginx error log
tail -f /var/log/nginx/error.log

# cPanel Apache logs
tail -f /usr/local/apache/logs/error_log
```

---

## Ubicaciones Comunes de Logs

### Windows:
- **XAMPP PHP**: `C:\xampp\php\logs\php_error_log`
- **XAMPP Apache**: `C:\xampp\apache\logs\error.log`
- **WAMP PHP**: `C:\wamp64\logs\php_error.log`
- **WAMP Apache**: `C:\wamp64\logs\apache_error.log`

### Linux/cPanel:
- **PHP Error Log (usuario)**: `~/logs/error_log` o `~/public_html/gac/logs/php_error.log`
- **Apache Error Log**: `/usr/local/apache/logs/error_log`
- **Nginx Error Log**: `/var/log/nginx/error.log`

---

## Verificar ubicación del log de PHP

### En PHP:
```php
<?php
echo "PHP Error Log: " . ini_get('error_log');
phpinfo();
?>
```

### En terminal:
```bash
# Ver configuración de PHP
php -i | grep error_log

# Ver ubicación del archivo php.ini
php --ini
```

---

## Habilitar logs si no están activos

### En php.ini:
```ini
log_errors = On
error_log = /ruta/a/tu/log/php_error.log
display_errors = Off  ; En producción siempre Off
display_startup_errors = Off
```

### En código PHP:
```php
ini_set('log_errors', 1);
ini_set('error_log', '/ruta/a/tu/log/php_error.log');
```
