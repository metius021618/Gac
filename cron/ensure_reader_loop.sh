#!/bin/bash
# GAC - Asegura que el lector continuo (sync_loop.py) esté corriendo.
# Si no está corriendo, lo inicia en segundo plano.
# Para inicio automático: configura un cron cada 2-5 minutos, ej.:
#   */2 * * * * /ruta/a/SISTEMA_GAC/cron/ensure_reader_loop.sh >> /ruta/a/logs/ensure_reader.log 2>&1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR" || exit 0

LOG_DIR="$ROOT_DIR/logs"
PID_FILE="$LOG_DIR/reader_loop.pid"
LOG_FILE="$LOG_DIR/sync_loop.log"
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

# Eliminar PID obsoleto
rm -f "$PID_FILE"
nohup "$PYTHON" "$SCRIPT_DIR/sync_loop.py" >> "$LOG_FILE" 2>&1 &
exit 0
