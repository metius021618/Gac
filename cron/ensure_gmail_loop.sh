#!/usr/bin/env bash
# GAC - Asegura que el bucle continuo Gmail API (gmail_loop.py) esté corriendo.
# Si no está corriendo, lo inicia en segundo plano.
#
# Site Tools → Cron Jobs (ejemplo cada 3 minutos):
#   */3 * * * * /home/USUARIO/www/new.pocoyoni.com/cron/ensure_gmail_loop.sh >> /home/USUARIO/www/new.pocoyoni.com/logs/ensure_gmail_loop.log 2>&1
#
# Intervalo entre lecturas Gmail: CRON_GMAIL_MIN_INTERVAL_SECONDS en .env (mínimo 60 s).

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR" || exit 0

LOG_DIR="$ROOT_DIR/logs"
PID_FILE="$LOG_DIR/gmail_loop.pid"
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
nohup "$PYTHON" "$SCRIPT_DIR/gmail_loop.py" >/dev/null 2>&1 &
exit 0
