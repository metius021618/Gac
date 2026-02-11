#!/usr/bin/env python3
"""
GAC - Bucle de sincronización cada 30 segundos
Ejecuta los 3 lectores de correo (Pocoyoni/IMAP, Gmail, Outlook) en paralelo
cada 30 segundos para tener siempre los últimos códigos en BD.

Uso (desde la raíz del proyecto SISTEMA_GAC):
  python cron/sync_loop.py

Para dejar corriendo en segundo plano:
  Linux/Mac: nohup python3 cron/sync_loop.py >> logs/sync_loop.log 2>&1 &
  Windows:   start /B python cron/sync_loop.py >> logs/sync_loop.log 2>&1
"""

import os
import sys
import time
import subprocess
import logging
from datetime import datetime

# Directorio raíz del proyecto (SISTEMA_GAC)
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.dirname(SCRIPT_DIR)
os.chdir(ROOT_DIR)

LOG_DIR = os.path.join(ROOT_DIR, 'logs')
LOG_FILE = os.path.join(LOG_DIR, 'sync_loop.log')
PID_FILE = os.path.join(LOG_DIR, 'reader_loop.pid')
if not os.path.isdir(LOG_DIR):
    os.makedirs(LOG_DIR, exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_FILE, encoding='utf-8'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

READERS = [
    ('cron/email_reader.py', 'Pocoyoni/IMAP'),
    ('cron/email_reader_gmail.py', 'Gmail'),
    ('cron/email_reader_outlook.py', 'Outlook'),
]

try:
    from cron.config import CRON_CONFIG
    INTERVAL_SECONDS = CRON_CONFIG.get('reader_loop_seconds', 10)
except Exception:
    INTERVAL_SECONDS = 10


def run_reader(script_path: str, name: str) -> bool:
    """Ejecuta un script de lectura. Devuelve True si terminó bien."""
    try:
        result = subprocess.run(
            [sys.executable, script_path],
            cwd=ROOT_DIR,
            capture_output=True,
            universal_newlines=True,
            timeout=120
        )
        if result.returncode != 0 and result.stderr:
            logger.warning("%s stderr: %s", name, result.stderr[:500])
        return result.returncode == 0
    except subprocess.TimeoutExpired:
        logger.warning("%s: timeout 120s", name)
        return False
    except Exception as e:
        logger.exception("%s: %s", name, e)
        return False


def run_all_parallel():
    """Lanza los 3 lectores en paralelo y espera a que terminen todos."""
    procs = []
    for script_rel, name in READERS:
        script_abs = os.path.join(ROOT_DIR, script_rel.replace('/', os.sep))
        if not os.path.isfile(script_abs):
            logger.warning("No encontrado: %s", script_abs)
            continue
        try:
            p = subprocess.Popen(
                [sys.executable, script_rel.replace('/', os.sep)],
                cwd=ROOT_DIR,
                stdout=subprocess.DEVNULL,
                stderr=subprocess.PIPE,
                universal_newlines=True
            )
            procs.append((p, name, script_rel))
        except Exception as e:
            logger.exception("Error al iniciar %s: %s", name, e)
    for p, name, script_rel in procs:
        try:
            _, err = p.communicate(timeout=120)
            if p.returncode != 0 and err:
                logger.warning("%s stderr: %s", name, err[:300])
        except subprocess.TimeoutExpired:
            p.kill()
            logger.warning("%s: timeout, terminado", name)
        except Exception as e:
            logger.warning("%s: %s", name, e)


def write_pid():
    """Escribir PID para que el panel sepa si el lector está corriendo."""
    try:
        with open(PID_FILE, 'w') as f:
            f.write(str(os.getpid()))
    except Exception as e:
        logger.warning("No se pudo escribir PID: %s", e)


def remove_pid():
    """Eliminar archivo PID al salir."""
    try:
        if os.path.isfile(PID_FILE):
            os.remove(PID_FILE)
    except Exception:
        pass


def main():
    logger.info("Sync loop iniciado (cada %d s). Raíz: %s", INTERVAL_SECONDS, ROOT_DIR)
    write_pid()
    cycle = 0
    try:
        while True:
            cycle += 1
            start = time.time()
            logger.info("--- Ciclo %d ---", cycle)
            run_all_parallel()
            elapsed = time.time() - start
            logger.info("Ciclo %d terminado en %.1f s. Esperando %d s...", cycle, elapsed, INTERVAL_SECONDS)
            time.sleep(INTERVAL_SECONDS)
    finally:
        remove_pid()


if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        logger.info("Sync loop detenido por el usuario")
        remove_pid()
        sys.exit(0)
