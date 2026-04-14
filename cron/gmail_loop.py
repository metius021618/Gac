#!/usr/bin/env python3
"""
GAC - Bucle continuo solo Gmail API (email_reader_gmail.py).

Ejecuta el lector Gmail una y otra vez con pausa entre ciclos (por defecto ≥60 s
por límites de la Gmail API; ver CRON_GMAIL_MIN_INTERVAL_SECONDS en .env).

Uso manual:
  python3 cron/gmail_loop.py

Segundo plano (Linux):
  nohup python3 cron/gmail_loop.py >> logs/gmail_loop.log 2>&1 &

Inicio automático: cron/ensure_gmail_loop.sh cada 2–5 min en Site Tools.

No sustituye al webhook Pub/Sub; convive con él. Útil si no quieres depender
solo de push/cron cada minuto para tener Gmail al día.
"""

import os
import sys
import time
import subprocess
import logging
import signal

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.dirname(SCRIPT_DIR)
os.chdir(ROOT_DIR)

LOG_DIR = os.path.join(ROOT_DIR, 'logs')
LOG_FILE = os.path.join(LOG_DIR, 'gmail_loop.log')
PID_FILE = os.path.join(LOG_DIR, 'gmail_loop.pid')
READER = os.path.join(ROOT_DIR, 'cron', 'email_reader_gmail.py')

if not os.path.isdir(LOG_DIR):
    os.makedirs(LOG_DIR, exist_ok=True)

_gmail_loop_handlers = [logging.FileHandler(LOG_FILE, encoding='utf-8')]
if sys.stderr.isatty():
    _gmail_loop_handlers.append(logging.StreamHandler(sys.stderr))

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=_gmail_loop_handlers,
)
logger = logging.getLogger(__name__)

try:
    from cron.config import CRON_CONFIG
    # Mismo mínimo que entre llamadas Gmail en sync_loop antiguo; no acelerar la API.
    INTERVAL_SECONDS = float(CRON_CONFIG.get('gmail_min_interval_seconds', 60))
except Exception:
    INTERVAL_SECONDS = 60.0

INTERVAL_SECONDS = max(60.0, INTERVAL_SECONDS)

READER_TIMEOUT = int(os.getenv('GMAIL_READER_TIMEOUT_SECONDS', '300'))


def _write_pid() -> None:
    try:
        with open(PID_FILE, 'w', encoding='utf-8') as f:
            f.write(str(os.getpid()))
    except OSError as e:
        logger.warning('No se pudo escribir PID: %s', e)


def _remove_pid() -> None:
    try:
        if os.path.isfile(PID_FILE):
            os.remove(PID_FILE)
    except OSError:
        pass


def _run_one_cycle() -> int:
    if not os.path.isfile(READER):
        logger.error('No existe %s', READER)
        return 127
    try:
        r = subprocess.run(
            [sys.executable, READER],
            cwd=ROOT_DIR,
            timeout=READER_TIMEOUT,
            env={**os.environ, 'PYTHONUNBUFFERED': '1'},
        )
        return int(r.returncode)
    except subprocess.TimeoutExpired:
        logger.warning('email_reader_gmail.py superó timeout %ss', READER_TIMEOUT)
        return 124
    except Exception as e:
        logger.exception('Error al ejecutar email_reader_gmail: %s', e)
        return 1


def main() -> None:
    logger.info(
        'gmail_loop iniciado (intervalo %.1f s entre ciclos). Raíz: %s',
        INTERVAL_SECONDS,
        ROOT_DIR,
    )
    _write_pid()

    def _stop(*_args):
        logger.info('Señal recibida; saliendo del bucle Gmail.')
        raise SystemExit(0)

    signal.signal(signal.SIGTERM, _stop)
    signal.signal(signal.SIGINT, _stop)

    cycle = 0
    try:
        while True:
            cycle += 1
            logger.info('--- Gmail API ciclo %d ---', cycle)
            rc = _run_one_cycle()
            if rc != 0:
                logger.warning('email_reader_gmail terminó con código %s', rc)
            logger.info(
                'Pausa %.1f s hasta el siguiente ciclo Gmail.',
                INTERVAL_SECONDS,
            )
            time.sleep(INTERVAL_SECONDS)
    finally:
        _remove_pid()
        logger.info('gmail_loop finalizado.')


if __name__ == '__main__':
    try:
        main()
    except SystemExit:
        _remove_pid()
        raise
