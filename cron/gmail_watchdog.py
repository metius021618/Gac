#!/usr/bin/env python3
"""
GAC - Watchdog permanente para Gmail.

Objetivo:
- Mantener un proceso vivo de supervisión Gmail (sin cron).
- Ejecutar reinicio diario del stack Gmail a las 03:00 (hora del servidor).
  Reinicio Gmail = limpiar cooldown + renew_gmail_watch.py + corrida manual de email_reader_gmail.py.
"""

import os
import sys
import time
import signal
import subprocess
import logging
from datetime import datetime

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.dirname(SCRIPT_DIR)
LOG_DIR = os.path.join(ROOT_DIR, "logs")
WATCHDOG_LOG = os.path.join(LOG_DIR, "gmail_watchdog.log")
WATCHDOG_PID = os.path.join(LOG_DIR, "gmail_watchdog.pid")
STATE_FILE = os.path.join(LOG_DIR, "gmail_watchdog_state.txt")
COOLDOWN_FILE = os.path.join(LOG_DIR, "gmail_rate_limit_until.txt")

RENEW_SCRIPT = os.path.join(SCRIPT_DIR, "renew_gmail_watch.py")
READER_SCRIPT = os.path.join(SCRIPT_DIR, "email_reader_gmail.py")

CHECK_EVERY_SECONDS = int(os.getenv("GMAIL_WATCHDOG_INTERVAL_SECONDS", "300"))
CHECK_EVERY_SECONDS = max(60, CHECK_EVERY_SECONDS)
DAILY_RESTART_HOUR = int(os.getenv("GMAIL_DAILY_RESTART_HOUR", "3"))
if DAILY_RESTART_HOUR < 0 or DAILY_RESTART_HOUR > 23:
    DAILY_RESTART_HOUR = 3

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


def _write_pid() -> None:
    try:
        with open(WATCHDOG_PID, "w", encoding="utf-8") as f:
            f.write(str(os.getpid()))
    except OSError as e:
        logger.warning("No se pudo escribir PID de gmail_watchdog: %s", e)


def _remove_pid() -> None:
    try:
        if os.path.isfile(WATCHDOG_PID):
            os.remove(WATCHDOG_PID)
    except OSError:
        pass


def _load_last_restart_date() -> str:
    try:
        if not os.path.isfile(STATE_FILE):
            return ""
        with open(STATE_FILE, "r", encoding="utf-8") as f:
            return (f.read() or "").strip()
    except Exception:
        return ""


def _save_last_restart_date(date_str: str) -> None:
    try:
        with open(STATE_FILE, "w", encoding="utf-8") as f:
            f.write(date_str)
    except Exception as e:
        logger.warning("No se pudo guardar estado de gmail_watchdog: %s", e)


def _run_script(script_path: str, label: str) -> bool:
    if not os.path.isfile(script_path):
        logger.error("%s no existe: %s", label, script_path)
        return False
    try:
        r = subprocess.run(
            [sys.executable, script_path],
            cwd=ROOT_DIR,
            capture_output=True,
            text=True,
            timeout=300,
            env={**os.environ, "PYTHONUNBUFFERED": "1"},
        )
        if r.stdout:
            logger.info("%s stdout:\n%s", label, r.stdout.strip())
        if r.stderr:
            logger.info("%s stderr:\n%s", label, r.stderr.strip())
        if r.returncode != 0:
            logger.warning("%s terminó con código %s", label, r.returncode)
            return False
        return True
    except Exception as e:
        logger.exception("Error ejecutando %s: %s", label, e)
        return False


def _restart_gmail_stack() -> None:
    logger.info("=== Reinicio diario Gmail iniciado ===")
    try:
        if os.path.isfile(COOLDOWN_FILE):
            os.remove(COOLDOWN_FILE)
            logger.info("Cooldown Gmail eliminado (%s).", COOLDOWN_FILE)
    except Exception as e:
        logger.warning("No se pudo eliminar cooldown Gmail: %s", e)

    _run_script(RENEW_SCRIPT, "renew_gmail_watch.py")
    _run_script(READER_SCRIPT, "email_reader_gmail.py")
    logger.info("=== Reinicio diario Gmail finalizado ===")


def main() -> None:
    logger.info(
        "gmail_watchdog iniciado. Verificación cada %ss. Reinicio diario: %02d:00. ROOT=%s",
        CHECK_EVERY_SECONDS,
        DAILY_RESTART_HOUR,
        ROOT_DIR,
    )
    _write_pid()

    def _stop(*_args):
        logger.info("Señal recibida; deteniendo gmail_watchdog.")
        raise SystemExit(0)

    signal.signal(signal.SIGTERM, _stop)
    signal.signal(signal.SIGINT, _stop)

    try:
        while True:
            now = datetime.now()
            today = now.strftime("%Y-%m-%d")
            last_restart = _load_last_restart_date()
            if now.hour == DAILY_RESTART_HOUR and last_restart != today:
                _restart_gmail_stack()
                _save_last_restart_date(today)
            else:
                logger.info("gmail_watchdog OK (esperando ventana diaria %02d:00).", DAILY_RESTART_HOUR)
            time.sleep(CHECK_EVERY_SECONDS)
    finally:
        _remove_pid()
        logger.info("gmail_watchdog finalizado.")


if __name__ == "__main__":
    try:
        main()
    except SystemExit as e:
        _remove_pid()
        raise e
