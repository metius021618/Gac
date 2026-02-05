#!/usr/bin/env python3
"""
GAC - Lector de correos SOLO Outlook/Hotmail (Microsoft Graph API)
Script independiente: solo lee cuentas type=outlook y guarda en codes con origin='outlook'.
No mezcla con IMAP ni Gmail. Para consultas con @outlook.com/@hotmail.com/@live.com se ejecuta este script.
"""

import sys
import os
import logging

script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)
os.chdir(script_dir)

from cron.config import CRON_CONFIG, LOG_CONFIG
from cron.database import Database
from cron.repositories import EmailAccountRepository, PlatformRepository, CodeRepository
from cron.email_filter import EmailFilterService
from cron.outlook_service import OutlookService

logging.basicConfig(
    level=getattr(logging, LOG_CONFIG['level'].upper(), logging.INFO),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_CONFIG['file']),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


def _normalize_outlook_email(email):
    """Normalizar email guest (ej. apipocoyoni_outlook.com#EXT#@tenant.onmicrosoft.com) a apipocoyoni@outlook.com."""
    if not email:
        return email
    email = email.strip().lower()
    if '#EXT#' in email:
        email = email.split('#EXT#')[0].strip()
        pos = email.rfind('_')
        if pos >= 0:
            email = email[:pos] + '@' + email[pos + 1:]
    return email


def _backfill_email_bodies(emails, limit=100):
    """Actualizar email_body de códigos ya guardados que lo tengan vacío."""
    if not emails:
        return 0
    updated = 0
    try:
        db = Database.get_connection()
        cursor = db.cursor()
        to_process = emails[:limit] if len(emails) > limit else emails
        for email_data in to_process:
            subject = email_data.get('subject', '')
            email_from = email_data.get('from', '')
            recipient = (email_data.get('to_primary', '') or '').strip().lower()
            if not recipient and email_data.get('to'):
                recipient = (email_data['to'][0] or '').strip().lower()
            body = email_data.get('body_html') or email_data.get('body_text') or email_data.get('body') or ''
            if not body or not subject:
                continue
            try:
                cursor.execute("""
                    SELECT id FROM codes
                    WHERE subject = %s AND email_from = %s AND recipient_email = %s
                      AND (email_body IS NULL OR email_body = '')
                    LIMIT 1
                """, (subject, email_from, recipient))
                row = cursor.fetchone()
                if row:
                    code_id = row['id'] if isinstance(row, dict) else row[0]
                    cursor.execute("UPDATE codes SET email_body = %s WHERE id = %s", (body, code_id))
                    db.commit()
                    updated += 1
            except Exception:
                try:
                    db.rollback()
                except Exception:
                    pass
        cursor.close()
    except Exception as e:
        logger.warning(f"Backfill de cuerpos: {e}")
    return updated


def main():
    logger.info("=" * 60)
    logger.info("GAC - Lector Outlook únicamente (email_reader_outlook.py)")
    logger.info("=" * 60)

    if not CRON_CONFIG['enabled']:
        logger.warning("Cron deshabilitado en configuración")
        return

    try:
        filter_service = EmailFilterService()
        outlook_accounts = EmailAccountRepository.find_by_type('outlook')
        if not outlook_accounts:
            logger.info("No hay cuentas Outlook habilitadas.")
            return

        total_codes_saved = 0
        for oaccount in outlook_accounts:
            oaccount_id = oaccount['id']
            oaccount_email = (oaccount.get('email') or '').strip().lower()
            recipient_email_normalized = _normalize_outlook_email(oaccount_email)  # Para guardar códigos con correo corto
            logger.info(f"Procesando cuenta Outlook: {oaccount_email} (ID: {oaccount_id})")
            try:
                outlook_service = OutlookService()
                emails = outlook_service.read_account(oaccount, max_messages=50)
                logger.info(f"  - Emails leídos: {len(emails)}")
                if not emails:
                    EmailAccountRepository.update_sync_status(oaccount_id, 'success')
                    continue
                filtered = filter_service.filter_by_subject(emails)
                logger.info(f"  - Emails filtrados: {len(filtered)}")
                if not filtered:
                    EmailAccountRepository.update_sync_status(oaccount_id, 'success')
                    continue
                records_saved = 0
                for email_data in filtered:
                    platform = email_data.get('matched_platform')
                    if not platform:
                        continue
                    platform_obj = PlatformRepository.find_by_name(platform)
                    if not platform_obj or not platform_obj.get('enabled'):
                        continue
                    email_from = email_data.get('from', '') or ''
                    recipient_email = (email_data.get('to_primary') or recipient_email_normalized or oaccount_email).strip().lower()
                    recipient_email = _normalize_outlook_email(recipient_email) or recipient_email
                    received_at = email_data.get('date', '')
                    subject = email_data.get('subject', '')
                    email_body = email_data.get('body_html') or email_data.get('body_text') or email_data.get('body') or ''
                    if CodeRepository.email_record_exists(oaccount_id, email_from, recipient_email, subject, received_at):
                        if email_body and CodeRepository.update_email_body_by_email(
                            oaccount_id, email_from, recipient_email, subject, received_at, email_body
                        ):
                            logger.info(f"  - ✓ Cuerpo actualizado para {recipient_email}")
                        continue
                    save_data = {
                        'email_account_id': oaccount_id,
                        'platform_id': platform_obj['id'],
                        'code': email_from,
                        'email_from': email_from,
                        'subject': subject,
                        'email_body': email_body,
                        'received_at': received_at,
                        'origin': 'outlook',
                        'recipient_email': recipient_email,
                    }
                    code_id = CodeRepository.save(save_data)
                    if code_id:
                        records_saved += 1
                        logger.info(f"  - ✓ Correo guardado: DE={email_from[:40]} → {recipient_email}")
                total_codes_saved += records_saved
                backfill_count = _backfill_email_bodies(emails, limit=100)
                if backfill_count:
                    logger.info(f"  - Cuerpos actualizados (backfill): {backfill_count}")
                EmailAccountRepository.update_sync_status(oaccount_id, 'success')
            except Exception as e:
                error_msg = str(e)
                logger.error(f"  - ✗ Error Outlook {oaccount_email}: {error_msg}")
                EmailAccountRepository.update_sync_status(oaccount_id, 'error', error_msg)

        logger.info("=" * 60)
        logger.info(f"Outlook completado. Total guardados: {total_codes_saved}")
        logger.info("=" * 60)
    except Exception as e:
        logger.error(f"Error fatal: {e}", exc_info=True)
        sys.exit(1)
    finally:
        Database.close_connections()


if __name__ == '__main__':
    main()
