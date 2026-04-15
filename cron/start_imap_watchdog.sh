#!/usr/bin/env bash
# GAC - Iniciar watchdog permanente de IMAP.
# Verifica si imap_watchdog.py ya está vivo; si no, lo arranca.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR" || exit 0

LOG_DIR="$ROOT_DIR/logs"
WATCHDOG_PID="$LOG_DIR/imap_watchdog.pid"
PYTHON="${PYTHON:-python3}"

mkdir -p "$LOG_DIR"

is_running() {
  [ ! -f "$WATCHDOG_PID" ] && return 1
  pid=$(cat "$WATCHDOG_PID" 2>/dev/null)
  [ -z "$pid" ] && return 1
  kill -0 "$pid" 2>/dev/null
}

if is_running; then
  exit 0
fi

rm -f "$WATCHDOG_PID"
nohup "$PYTHON" "$SCRIPT_DIR/imap_watchdog.py" >/dev/null 2>&1 &
exit 0
