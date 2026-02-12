# Logs del cron y lectores (GAC)

## Matar procesos del lector (sync_loop)

```bash
# Opción 1: por PID (sustituir por los que salgan en ps)
kill 1589105 2538450

# Opción 2: matar todos los sync_loop de una vez
pkill -f "sync_loop.py"
```

## Dónde están los logs

En el servidor, dentro del proyecto (ej. `/home/pocoavbb/app.pocoyoni.com/`):

| Archivo | Contenido |
|---------|-----------|
| **logs/cron.log** | Lo que hace cada lector: correos leídos, cuántos pasan el filtro de asuntos, qué se guarda, asuntos que no coinciden con la BD. |
| **logs/sync_loop.log** | Ciclos del bucle, tiempo por ciclo, errores al lanzar lectores, timeouts. |

## Ver logs en vivo

```bash
cd /home/pocoavbb/app.pocoyoni.com

# Ver lo que recibe y guarda Gmail/lectores (recomendado para depurar “no encuentra código”)
tail -f logs/cron.log

# Ver ciclos del bucle
tail -f logs/sync_loop.log
```

## Qué buscar en cron.log

- **Emails leídos: N** – Cuántos correos trajo Gmail en este ciclo.
- **Recibido[i]: asunto='...' → destinatario=...** – Asunto y destinatario de cada correo recibido (para comparar con lo que ves en Gmail).
- **Emails filtrados: N** – Cuántos pasaron el filtro de asuntos (tabla “Asuntos de correo”). Si aquí sale 0 y arriba “Emails leídos” > 0, el asunto no está en la BD o no coincide.
- **Asuntos no coinciden con BD** – Lista de asuntos recibidos que no coinciden; puedes copiarlos y añadirlos en Admin → Asuntos de correo.
- **✓ Correo guardado: DE=... → destinatario** – Confirma que ese correo se guardó en la BD.

## Reiniciar el lector después de cambios

```bash
cd /home/pocoavbb/app.pocoyoni.com
pkill -f "sync_loop.py"
nohup python3 cron/sync_loop.py >> logs/sync_loop.log 2>&1 &
```
