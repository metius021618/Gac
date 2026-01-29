# Cómo Encontrar los Logs de PHP en cPanel

## Comandos para encontrar los logs:

```bash
# 1. Verificar ubicación del log de PHP configurado
php -i | grep error_log

# 2. Buscar archivos de log comunes
find ~ -name "*error*log" -type f 2>/dev/null | head -20

# 3. Ver logs de Apache (puede contener errores PHP)
tail -f /usr/local/apache/logs/error_log

# 4. Ver logs específicos del dominio
tail -f ~/logs/app.pocoyoni.com.error_log

# 5. Ver logs de PHP en el directorio público
tail -f ~/public_html/gac/SISTEMA_GAC/logs/*.log

# 6. Verificar si hay logs en el directorio del proyecto
ls -la ~/public_html/gac/SISTEMA_GAC/logs/

# 7. Ver logs de cPanel
tail -f ~/logs/error_log
tail -f ~/logs/access_log
```

## Ubicaciones comunes en cPanel:

1. **Logs del dominio**: `~/logs/app.pocoyoni.com.error_log`
2. **Logs de Apache**: `/usr/local/apache/logs/error_log`
3. **Logs del usuario**: `~/logs/error_log` (puede no existir)
4. **Logs del proyecto**: `~/public_html/gac/SISTEMA_GAC/logs/`

## Verificar configuración de PHP:

```bash
# Ver dónde está configurado el error_log
php -i | grep -i "error_log"

# Ver archivo php.ini usado
php --ini
```
