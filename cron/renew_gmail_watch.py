#!/usr/bin/env python3
"""
GAC - Renovación del Gmail Watch (users.watch)
Ejecutar diariamente (cron) para renovar el watch antes de que expire (~7 días).
Registra el watch, guarda historyId y expiración en settings para uso del webhook/worker.

Uso:
  python cron/renew_gmail_watch.py

Cron diario sugerido (ej. 3:00):
  0 3 * * * cd /ruta/SISTEMA_GAC && python3 cron/renew_gmail_watch.py >> logs/renew_gmail_watch.log 2>&1
"""

import os
import sys

script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)
os.chdir(parent_dir)

from cron.config import LOG_CONFIG
from cron.repositories import EmailAccountRepository, SettingsRepository
from cron.gmail_service import GmailService
import logging

logging.basicConfig(
    level=getattr(logging, LOG_CONFIG.get('level', 'info').upper(), logging.INFO),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[logging.StreamHandler()]
)
logger = logging.getLogger(__name__)


def main():
    logger.info("Renovación Gmail Watch")
    gaccount = EmailAccountRepository.get_gmail_matrix_account()
    if not gaccount:
        logger.warning("No hay cuenta Gmail matriz configurada. Nada que renovar.")
        return 0
    try:
        gmail = GmailService()
        result = gmail.setup_watch(gaccount)
        if not result:
            logger.warning("setup_watch no devolvió datos. Revisar GMAIL_PUBSUB_TOPIC y credenciales.")
            return 1
        history_id = result.get('historyId')
        expiration = result.get('expiration', '')
        if history_id:
            SettingsRepository.set('gmail_last_history_id', history_id)
            logger.info("gmail_last_history_id guardado: %s", history_id)
        if expiration:
            SettingsRepository.set('gmail_watch_expiration', expiration)
            logger.info("gmail_watch_expiration guardado: %s", expiration)
        logger.info("Renovación Gmail Watch completada.")
        return 0
    except Exception as e:
        logger.exception("Error renovando Gmail Watch: %s", e)
        return 1


if __name__ == '__main__':
    sys.exit(main())
