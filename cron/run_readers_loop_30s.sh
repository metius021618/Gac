#!/bin/bash
# GAC - Bucle: ejecutar los 3 lectores cada 30 segundos durante ~4 minutos.
# Pensado para cPanel: Cron cada 5 minutos ejecuta este script.
# Así obtienes sincronizaciones cada 30 s mientras el script está vivo; luego
# el siguiente cron lo vuelve a lanzar (sin solaparse).
# Cron ejemplo: */5 * * * * /ruta/cron/run_readers_loop_30s.sh >> /ruta/logs/cron.log 2>&1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR" || exit 1

mkdir -p "$ROOT_DIR/logs"
LOG="$ROOT_DIR/logs/cron.log"
PYTHON="${PYTHON:-/bin/python3}"
RUN_FOR_SECONDS=240   # 4 minutos (deja margen antes del siguiente cron a los 5 min)
INTERVAL_SECONDS=30

run_readers() {
  "$PYTHON" "$SCRIPT_DIR/email_reader.py" >> "$LOG" 2>&1 &
  "$PYTHON" "$SCRIPT_DIR/email_reader_gmail.py" >> "$LOG" 2>&1 &
  "$PYTHON" "$SCRIPT_DIR/email_reader_outlook.py" >> "$LOG" 2>&1 &
  wait
}

start=$(date +%s)
while true; do
  run_readers
  now=$(date +%s)
  elapsed=$((now - start))
  [ "$elapsed" -ge "$RUN_FOR_SECONDS" ] && break
  sleep "$INTERVAL_SECONDS"
done
