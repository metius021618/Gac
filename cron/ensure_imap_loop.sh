#!/usr/bin/env bash
# GAC - Asegura que el bucle continuo solo IMAP (imap_loop.py) esté corriendo.
# Si no está corriendo, lo inicia en segundo plano.
#
# Site Tools → Cron Jobs (ejemplo cada 3 minutos):
#   */3 * * * * /home/USUARIO/www/new.pocoyoni.com/cron/ensure_imap_loop.sh >> /home/USUARIO/www/new.pocoyoni.com/logs/ensure_imap_loop.log 2>&1
#
# Importante: desactiva el cron que ejecuta email_reader.py cada minuto si usas este script,
# para no leer el buzón dos veces en paralelo.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR" || exit 0

LOG_DIR="$ROOT_DIR/logs"
PID_FILE="$LOG_DIR/imap_loop.pid"
LOG_FILE="$LOG_DIR/imap_loop.log"
PYTHON="${PYTHON:-python3}"

mkdir -p "$LOG_DIR"

is_running() {
  [ ! -f "$PID_FILE" ] && return 1
  pid=$(cat "$PID_FILE" 2>/dev/null)
  [ -z "$pid" ] && return 1
  kill -0 "$pid" 2>/dev/null
}

if is_running; then
  exit 0
fi

rm -f "$PID_FILE"
# imap_loop.py escribe ya en logs/imap_loop.log (FileHandler); no redirigir al mismo archivo
nohup "$PYTHON" "$SCRIPT_DIR/imap_loop.py" >/dev/null 2>&1 &
exit 0
