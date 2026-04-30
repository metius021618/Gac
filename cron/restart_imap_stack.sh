#!/usr/bin/env bash
# GAC - Reinicio limpio del stack IMAP (watchdog + loop).
# Uso recomendado: cron diario de madrugada (3:00 o 4:00) para limpiar estados colgados.

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR" || exit 0

LOG_DIR="$ROOT_DIR/logs"
RESTART_LOG="$LOG_DIR/restart_imap_stack.log"
WATCHDOG_PID="$LOG_DIR/imap_watchdog.pid"
LOOP_PID="$LOG_DIR/imap_loop.pid"

mkdir -p "$LOG_DIR"

log() {
  printf '%s %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1" >> "$RESTART_LOG"
}

kill_by_pidfile() {
  local pid_file="$1"
  local name="$2"
  if [ -f "$pid_file" ]; then
    local pid
    pid="$(cat "$pid_file" 2>/dev/null || true)"
    if [ -n "${pid:-}" ] && kill -0 "$pid" 2>/dev/null; then
      kill "$pid" 2>/dev/null || true
      sleep 1
      if kill -0 "$pid" 2>/dev/null; then
        kill -9 "$pid" 2>/dev/null || true
        log "$name forzado con SIGKILL (pid=$pid)"
      else
        log "$name detenido con SIGTERM (pid=$pid)"
      fi
    else
      log "$name no estaba activo (pid file stale)"
    fi
  else
    log "$name sin pid file"
  fi
}

log "=== Reinicio IMAP stack iniciado ==="

# 1) Parar watchdog y loop (en ese orden para evitar relanzado inmediato).
kill_by_pidfile "$WATCHDOG_PID" "imap_watchdog"
kill_by_pidfile "$LOOP_PID" "imap_loop"

# 2) Limpiar pid files viejos.
rm -f "$WATCHDOG_PID" "$LOOP_PID"

# 3) Arrancar watchdog (él levanta imap_loop si no existe).
bash "$SCRIPT_DIR/start_imap_watchdog.sh"
sleep 2

if [ -f "$WATCHDOG_PID" ]; then
  wpid="$(cat "$WATCHDOG_PID" 2>/dev/null || true)"
  log "watchdog arrancado (pid=${wpid:-desconocido})"
else
  log "ERROR: watchdog no generó pid file"
fi

if [ -f "$LOOP_PID" ]; then
  lpid="$(cat "$LOOP_PID" 2>/dev/null || true)"
  log "imap_loop activo (pid=${lpid:-desconocido})"
else
  log "AVISO: imap_loop aún sin pid file (watchdog lo levantará en su próximo chequeo)"
fi

log "=== Reinicio IMAP stack finalizado ==="
exit 0
