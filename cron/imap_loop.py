#!/usr/bin/env python3
"""
GAC - Bucle continuo solo IMAP (cuenta maestra @pocoyoni.com).

Ejecuta `email_reader.py` una y otra vez con pausa entre ciclos. Pensado para
dejar UN proceso largo en lugar de un cron cada minuto.

Uso manual:
  python3 cron/imap_loop.py

Segundo plano (Linux):
  nohup python3 cron/imap_loop.py >> logs/imap_loop.log 2>&1 &

Inicio automático recomendado: `cron/ensure_imap_loop.sh` cada 2–5 min desde
Site Tools (solo arranca el bucle si no está vivo). Desactiva el cron que
ejecuta `email_reader.py` cada minuto para no duplicar lecturas.

No incluye Gmail ni Outlook; para eso sigue existiendo `sync_loop.py`.
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
LOG_FILE = os.path.join(LOG_DIR, 'imap_loop.log')
PID_FILE = os.path.join(LOG_DIR, 'imap_loop.pid')
READER = os.path.join(ROOT_DIR, 'cron', 'email_reader.py')

if not os.path.isdir(LOG_DIR):
    os.makedirs(LOG_DIR, exist_ok=True)

_imap_loop_handlers = [logging.FileHandler(LOG_FILE, encoding='utf-8')]
if sys.stderr.isatty():
    _imap_loop_handlers.append(logging.StreamHandler(sys.stderr))

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=_imap_loop_handlers,
)
logger = logging.getLogger(__name__)

try:
    from cron.config import CRON_CONFIG
    INTERVAL_SECONDS = float(CRON_CONFIG.get('reader_loop_seconds', 5))
except Exception:
    INTERVAL_SECONDS = 5.0

# Mínimo razonable en hosting compartido (evita fork storm / límites CPU)
INTERVAL_SECONDS = max(1.0, INTERVAL_SECONDS)

READER_TIMEOUT = int(os.getenv('IMAP_READER_TIMEOUT_SECONDS', '600'))


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
    """Ejecuta un ciclo de email_reader.py. Devuelve código de salida."""
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
        logger.warning('email_reader.py superó timeout %ss', READER_TIMEOUT)
        return 124
    except Exception as e:
        logger.exception('Error al ejecutar email_reader: %s', e)
        return 1


def main() -> None:
    logger.info(
        'imap_loop iniciado (intervalo %.1f s entre ciclos). Raíz: %s',
        INTERVAL_SECONDS,
        ROOT_DIR,
    )
    _write_pid()

    def _stop(*_args):
        logger.info('Señal recibida; saliendo del bucle IMAP.')
        raise SystemExit(0)

    signal.signal(signal.SIGTERM, _stop)
    signal.signal(signal.SIGINT, _stop)

    cycle = 0
    try:
        while True:
            cycle += 1
            logger.info('--- IMAP ciclo %d ---', cycle)
            rc = _run_one_cycle()
            if rc != 0:
                logger.warning('email_reader terminó con código %s', rc)
            logger.info(
                'Pausa %.1f s hasta el siguiente ciclo IMAP.',
                INTERVAL_SECONDS,
            )
            time.sleep(INTERVAL_SECONDS)
    finally:
        _remove_pid()
        logger.info('imap_loop finalizado.')


if __name__ == '__main__':
    try:
        main()
    except SystemExit as e:
        _remove_pid()
        raise e
