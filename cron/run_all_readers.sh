#!/bin/bash
# GAC - Ejecutar los 3 lectores de correo (Pocoyoni, Gmail, Outlook).
# Para cPanel: configura un Cron Job que ejecute este script cada 1 minuto.
# Ejemplo: * * * * * /home/usuario/public_html/gac/cron/run_all_readers.sh >> /home/usuario/public_html/gac/logs/cron.log 2>&1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR" || exit 1

mkdir -p "$ROOT_DIR/logs"
LOG="$ROOT_DIR/logs/cron.log"
PYTHON="${PYTHON:-/usr/bin/python3}"

# Ejecutar los 3 en paralelo
"$PYTHON" "$SCRIPT_DIR/email_reader.py" >> "$LOG" 2>&1 &
"$PYTHON" "$SCRIPT_DIR/email_reader_gmail.py" >> "$LOG" 2>&1 &
"$PYTHON" "$SCRIPT_DIR/email_reader_outlook.py" >> "$LOG" 2>&1 &
wait
