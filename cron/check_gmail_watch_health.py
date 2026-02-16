#!/usr/bin/env python3
"""
GAC - Monitor de salud del Gmail Watch (event-driven)
Verifica:
  1) Si no llegan eventos en X horas → ALERT (watch puede estar expirado o Pub/Sub caído).
  2) Si la expiración del watch está próxima o pasada → ALERT/WARNING.

Ejecutar desde cron cada pocas horas (ej. cada 6 h) cuando CRON_GMAIL_EVENT_DRIVEN=true.
Si hay alerta, exit(1) para que cron pueda enviar mail al admin.

Uso:
  python cron/check_gmail_watch_health.py

Cron ejemplo:
  0 */6 * * * cd /ruta/SISTEMA_GAC && python3 cron/check_gmail_watch_health.py >> logs/gmail_watch_health.log 2>&1
"""

import os
import sys
import time
from datetime import datetime

script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)
os.chdir(parent_dir)

from cron.config import CRON_CONFIG, LOG_CONFIG
from cron.repositories import SettingsRepository
import logging

logging.basicConfig(
    level=getattr(logging, LOG_CONFIG.get('level', 'info').upper(), logging.INFO),
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[logging.StreamHandler()]
)
logger = logging.getLogger(__name__)

ALERT = 60
logging.addLevelName(ALERT, 'ALERT')


def main():
    if not CRON_CONFIG.get('gmail_event_driven'):
        logger.info("Gmail no está en modo event-driven; monitor no aplica.")
        return 0

    exit_code = 0
    now_ts = time.time()
    alert_hours = CRON_CONFIG.get('gmail_no_event_alert_hours', 24)

    # 1) ¿Hace cuánto llegó el último evento?
    last_event_at = SettingsRepository.get('gmail_last_event_at', '')
    if last_event_at:
        try:
            last_ts = int(last_event_at)
            hours_ago = (now_ts - last_ts) / 3600.0
            if hours_ago > alert_hours:
                logger.log(
                    ALERT,
                    "ALERT: No se han recibido eventos Gmail en %.1f horas (umbral %d h). "
                    "Revisar watch (renew_gmail_watch.py), Pub/Sub y webhook.",
                    hours_ago, alert_hours
                )
                exit_code = 1
            else:
                logger.info("Último evento Gmail hace %.1f h (OK, umbral %d h).", hours_ago, alert_hours)
        except (TypeError, ValueError):
            logger.warning("gmail_last_event_at inválido: %s", last_event_at)
    else:
        logger.warning(
            "gmail_last_event_at no definido (aún no se procesó ningún push). "
            "Si hace días que está en event-driven, revisar webhook y renew_gmail_watch."
        )
        # No exit_code=1 la primera vez; podría ser instalación reciente

    # 2) ¿La expiración del watch está próxima o pasada?
    expiration_ms = SettingsRepository.get('gmail_watch_expiration', '')
    if expiration_ms:
        try:
            exp_ts = int(expiration_ms) / 1000.0
            exp_dt = datetime.utcfromtimestamp(exp_ts)
            hours_to_exp = (exp_ts - now_ts) / 3600.0
            if hours_to_exp < 0:
                logger.log(
                    ALERT,
                    "ALERT: Gmail Watch EXPIRADO desde %.1f h (expiración: %s UTC). Ejecutar renew_gmail_watch.py.",
                    -hours_to_exp, exp_dt.strftime('%Y-%m-%d %H:%M:%S')
                )
                exit_code = 1
            elif hours_to_exp < 24:
                logger.warning(
                    "Gmail Watch expira en %.1f h (%s UTC). Ejecutar renew_gmail_watch.py pronto.",
                    hours_to_exp, exp_dt.strftime('%Y-%m-%d %H:%M:%S')
                )
                exit_code = 1
            else:
                logger.info("Gmail Watch expira en %.1f h (%s UTC).", hours_to_exp, exp_dt.strftime('%Y-%m-%d %H:%M:%S'))
        except (TypeError, ValueError, OSError):
            logger.warning("gmail_watch_expiration inválido: %s", expiration_ms)
    else:
        logger.warning("gmail_watch_expiration no definido; ejecutar renew_gmail_watch.py al menos una vez.")

    if exit_code == 0:
        logger.info("Gmail Watch health OK.")
    return exit_code


if __name__ == '__main__':
    sys.exit(main())
