#!/usr/bin/env python3
"""
GAC - Worker de procesamiento ante evento Gmail (history.list + metadata + full solo si match)
Invocado por gmail_webhook_receiver cuando llega un push de Pub/Sub.
Flujo: leer last_history_id de BD -> history.list -> IDs nuevos -> metadata scan ->
filtro por asunto -> solo si match: full download -> guardar en codes con is_current.
Al final guarda el new history_id en BD.
"""

import os
import sys
import argparse
import logging
import time

script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)
os.chdir(parent_dir)

from cron.config import LOG_CONFIG
from cron.database import Database
from cron.repositories import (
    EmailAccountRepository,
    PlatformRepository,
    CodeRepository,
    SettingsRepository,
)
from cron.email_filter import EmailFilterService
from cron.gmail_service import GmailService

logging.basicConfig(
    level=getattr(logging, LOG_CONFIG.get('level', 'info').upper(), logging.INFO),
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[logging.StreamHandler()]
)
logger = logging.getLogger(__name__)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--history-id', required=True, help='Nuevo historyId del push (se guarda tras procesar)')
    args = parser.parse_args()
    new_history_id = (args.history_id or '').strip()
    if not new_history_id:
        logger.warning("Falta --history-id")
        return 1

    gaccount = EmailAccountRepository.get_gmail_matrix_account()
    if not gaccount:
        logger.warning("No hay cuenta Gmail matriz.")
        return 0

    last_history_id = SettingsRepository.get('gmail_last_history_id', '')
    if not last_history_id:
        logger.info("No hay gmail_last_history_id en BD; guardando el recibido y saliendo (próximo push usará history.list).")
        SettingsRepository.set('gmail_last_history_id', new_history_id)
        SettingsRepository.set('gmail_last_event_at', str(int(time.time())))
        return 0

    gmail = GmailService()
    service = gmail._build_service(gaccount)
    if not service:
        logger.error("No se pudo construir cliente Gmail.")
        return 1

    msg_ids, _ = gmail.fetch_history_message_ids(gaccount, last_history_id)
    if not msg_ids:
        logger.info("history.list no devolvió mensajes nuevos; actualizando historyId.")
        SettingsRepository.set('gmail_last_history_id', new_history_id)
        SettingsRepository.set('gmail_last_event_at', str(int(time.time())))
        return 0

    account_email = (gaccount.get('email') or '').strip().lower()
    filter_service = EmailFilterService()
    saved = 0
    for msg_id in msg_ids:
        # Duplication safe: si Pub/Sub reenvía evento, gmail_message_id UNIQUE evita insert duplicado
        if CodeRepository.gmail_message_id_exists(msg_id):
            logger.info("msg_id=%s omitido (ya guardado)", msg_id)
            continue
        meta = gmail.get_message_metadata(service, msg_id, account_email)
        if not meta:
            logger.info("msg_id=%s sin metadata, omitido", msg_id)
            continue
        # Un solo “email” para filtrar por asunto
        subject = meta.get('subject', '') or '(sin asunto)'
        logger.info("leyendo msg_id=%s asunto=%s", msg_id, subject[:80] + ('...' if len(subject) > 80 else ''))
        filtered = filter_service.filter_by_subject([meta])
        if not filtered:
            logger.info("msg_id=%s no guardado: asunto no coincide con ninguno de email_subjects (coincidencia exacta)", msg_id)
            continue
        email_data = filtered[0]
        platform = email_data.get('matched_platform')
        if not platform:
            logger.info("msg_id=%s no guardado: sin plataforma asignada", msg_id)
            continue
        platform_obj = PlatformRepository.find_by_name(platform)
        if not platform_obj or not platform_obj.get('enabled'):
            logger.info("msg_id=%s no guardado: plataforma %s no existe o está deshabilitada", msg_id, platform)
            continue
        full = gmail.get_message_full(service, msg_id, account_email)
        if not full:
            logger.info("msg_id=%s no guardado: no se pudo obtener cuerpo del mensaje", msg_id)
            continue
        recipient_email = (full.get('to_primary') or account_email).strip().lower()
        save_data = {
            'email_account_id': gaccount['id'],
            'platform_id': platform_obj['id'],
            'code': full.get('from', ''),
            'email_from': full.get('from', ''),
            'subject': full.get('subject', ''),
            'email_body': full.get('body_html') or full.get('body_text') or full.get('body', ''),
            'received_at': full.get('date', ''),
            'origin': 'gmail',
            'recipient_email': recipient_email,
            'email_date': full.get('date'),
            'gmail_message_id': msg_id,
        }
        code_id = CodeRepository.save_otp_current(save_data)
        if code_id:
            saved += 1
            logger.info("OTP guardado: msg_id=%s plataforma=%s -> %s", msg_id, platform, recipient_email)

    SettingsRepository.set('gmail_last_history_id', new_history_id)
    # Para monitor de salud: último evento procesado (check_gmail_watch_health.py alerta si no hay eventos en X h)
    SettingsRepository.set('gmail_last_event_at', str(int(time.time())))
    logger.info("Procesados %d mensajes, guardados %d. historyId actualizado.", len(msg_ids), saved)
    return 0


if __name__ == '__main__':
    try:
        sys.exit(main())
    finally:
        Database.close_connections()
