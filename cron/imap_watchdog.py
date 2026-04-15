#!/usr/bin/env python3
"""
GAC - Watchdog permanente para IMAP loop.

Objetivo:
- Verificar cada N segundos si `imap_loop.py` está vivo.
- Si no está vivo, arrancarlo.
- Si está vivo, no hacer nada.

Uso:
  python3 cron/imap_watchdog.py

Segundo plano:
  nohup python3 cron/imap_watchdog.py >> logs/imap_watchdog.log 2>&1 &
"""

import os
import sys
import time
import signal
import subprocess
import logging

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.dirname(SCRIPT_DIR)
LOG_DIR = os.path.join(ROOT_DIR, "logs")
WATCHDOG_LOG = os.path.join(LOG_DIR, "imap_watchdog.log")
WATCHDOG_PID = os.path.join(LOG_DIR, "imap_watchdog.pid")
IMAP_LOOP_PID = os.path.join(LOG_DIR, "imap_loop.pid")
IMAP_LOOP_SCRIPT = os.path.join(SCRIPT_DIR, "imap_loop.py")

CHECK_EVERY_SECONDS = int(os.getenv("IMAP_WATCHDOG_INTERVAL_SECONDS", "300"))
CHECK_EVERY_SECONDS = max(60, CHECK_EVERY_SECONDS)

if not os.path.isdir(LOG_DIR):
    os.makedirs(LOG_DIR, exist_ok=True)

_handlers = [logging.FileHandler(WATCHDOG_LOG, encoding="utf-8")]
if sys.stderr.isatty():
    _handlers.append(logging.StreamHandler(sys.stderr))

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=_handlers,
)
logger = logging.getLogger(__name__)


def _write_watchdog_pid() -> None:
    try:
        with open(WATCHDOG_PID, "w", encoding="utf-8") as f:
            f.write(str(os.getpid()))
    except OSError as e:
        logger.warning("No se pudo escribir PID de watchdog: %s", e)


def _remove_watchdog_pid() -> None:
    try:
        if os.path.isfile(WATCHDOG_PID):
            os.remove(WATCHDOG_PID)
    except OSError:
        pass


def _is_pid_running(pid: str) -> bool:
    if not pid:
        return False
    try:
        os.kill(int(pid), 0)
        return True
    except Exception:
        return False


def _imap_loop_running() -> bool:
    if not os.path.isfile(IMAP_LOOP_PID):
        return False
    try:
        with open(IMAP_LOOP_PID, "r", encoding="utf-8") as f:
            pid = (f.read() or "").strip()
        return _is_pid_running(pid)
    except Exception:
        return False


def _start_imap_loop() -> bool:
    if not os.path.isfile(IMAP_LOOP_SCRIPT):
        logger.error("No existe %s", IMAP_LOOP_SCRIPT)
        return False
    try:
        # imap_loop.py ya escribe su propio log; no redirigimos a imap_loop.log aquí.
        subprocess.Popen(
            [sys.executable, IMAP_LOOP_SCRIPT],
            cwd=ROOT_DIR,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            env={**os.environ, "PYTHONUNBUFFERED": "1"},
        )
        logger.info("imap_loop.py no estaba activo: relanzado.")
        return True
    except Exception as e:
        logger.exception("No se pudo arrancar imap_loop.py: %s", e)
        return False


def main() -> None:
    logger.info(
        "imap_watchdog iniciado. Verificación cada %ss. ROOT=%s",
        CHECK_EVERY_SECONDS,
        ROOT_DIR,
    )
    _write_watchdog_pid()

    def _stop(*_args):
        logger.info("Señal recibida; deteniendo imap_watchdog.")
        raise SystemExit(0)

    signal.signal(signal.SIGTERM, _stop)
    signal.signal(signal.SIGINT, _stop)

    try:
        while True:
            if _imap_loop_running():
                logger.info("imap_loop OK (en ejecución).")
            else:
                logger.warning("imap_loop detenido/no encontrado. Intentando iniciar...")
                _start_imap_loop()
            time.sleep(CHECK_EVERY_SECONDS)
    finally:
        _remove_watchdog_pid()
        logger.info("imap_watchdog finalizado.")


if __name__ == "__main__":
    try:
        main()
    except SystemExit as e:
        _remove_watchdog_pid()
        raise e
