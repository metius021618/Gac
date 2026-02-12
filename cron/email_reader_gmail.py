#!/usr/bin/env python3
"""
GAC - Lector de correos SOLO Gmail (Gmail API)
Script independiente: solo lee cuentas type=gmail y guarda en codes con origin='gmail'.
No mezcla con IMAP. Para consultas con @gmail.com se ejecuta este script.
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
from cron.gmail_service import GmailService, is_gmail_rate_limited

logging.basicConfig(
    level=getattr(logging, LOG_CONFIG['level'].upper(), logging.INFO),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_CONFIG['file']),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


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
    logger.info("GAC - Lector Gmail únicamente (email_reader_gmail.py)")
    logger.info("=" * 60)

    if not CRON_CONFIG['enabled']:
        logger.warning("Cron deshabilitado en configuración")
        return

    limited, until_utc = is_gmail_rate_limited()
    if limited and until_utc:
        logger.info("Gmail omitido (límite 429): no se llamará a la API hasta %s UTC", until_utc.strftime('%Y-%m-%d %H:%M:%S'))
        return

    try:
        filter_service = EmailFilterService()
        gmail_accounts = EmailAccountRepository.find_by_type('gmail')
        if not gmail_accounts:
            logger.info("No hay cuentas Gmail habilitadas.")
            return

        total_codes_saved = 0
        for gaccount in gmail_accounts:
            gaccount_id = gaccount['id']
            gaccount_email = (gaccount.get('email') or '').strip().lower()
            logger.info(f"Procesando cuenta Gmail: {gaccount_email} (ID: {gaccount_id})")
            try:
                gmail_service = GmailService()
                max_msg = CRON_CONFIG.get('gmail_max_messages', 20)
                emails = gmail_service.read_account(gaccount, max_messages=max_msg)
                logger.info(f"  - Emails leídos: {len(emails)}")
                for i, e in enumerate(emails[:5]):
                    logger.info(f"  - Recibido[{i}]: asunto=%r → destinatario=%s", (e.get('subject') or '')[:70], e.get('to_primary') or '')
                if not emails:
                    EmailAccountRepository.update_sync_status(gaccount_id, 'success')
                    continue
                filtered = filter_service.filter_by_subject(emails)
                logger.info(f"  - Emails filtrados: {len(filtered)}")
                if not filtered and emails:
                    subs = [e.get('subject', '')[:60] for e in emails[:5]]
                    logger.info(f"  - Asuntos no coinciden con BD (añádelos en Asuntos de correo): %s", subs)
                if not filtered:
                    EmailAccountRepository.update_sync_status(gaccount_id, 'success')
                    continue
                records_saved = 0
                for email_data in filtered:
                    platform = email_data.get('matched_platform')
                    if not platform:
                        logger.info(f"  - Saltado (sin plataforma): asunto=%s", (email_data.get('subject') or '')[:50])
                        continue
                    platform_obj = PlatformRepository.find_by_name(platform)
                    if not platform_obj or not platform_obj.get('enabled'):
                        logger.info(f"  - Saltado (plataforma %s no existe o deshabilitada): asunto=%s", platform, (email_data.get('subject') or '')[:50])
                        continue
                    email_from = email_data.get('from', '') or ''
                    recipient_email = (email_data.get('to_primary') or gaccount_email).strip().lower()
                    received_at = email_data.get('date', '')
                    subject = email_data.get('subject', '')
                    email_body = email_data.get('body_html') or email_data.get('body_text') or email_data.get('body') or ''
                    if CodeRepository.email_record_exists(gaccount_id, email_from, recipient_email, subject, received_at):
                        if email_body and CodeRepository.update_email_body_by_email(
                            gaccount_id, email_from, recipient_email, subject, received_at, email_body
                        ):
                            logger.info(f"  - ✓ Cuerpo actualizado para {recipient_email}")
                        else:
                            logger.info(f"  - Ya existía en BD (no se guarda de nuevo): asunto=%s → %s", subject[:50], recipient_email)
                        continue
                    save_data = {
                        'email_account_id': gaccount_id,
                        'platform_id': platform_obj['id'],
                        'code': email_from,
                        'email_from': email_from,
                        'subject': subject,
                        'email_body': email_body,
                        'received_at': received_at,
                        'origin': 'gmail',
                        'recipient_email': recipient_email,
                    }
                    code_id = CodeRepository.save(save_data)
                    if code_id:
                        records_saved += 1
                        logger.info(f"  - ✓ Correo guardado: DE={email_from[:40]} → {recipient_email}")
                    else:
                        logger.warning(f"  - ✗ Save falló (BD): asunto=%s → %s", subject[:50], recipient_email)
                total_codes_saved += records_saved
                backfill_count = _backfill_email_bodies(emails, limit=100)
                if backfill_count:
                    logger.info(f"  - Cuerpos actualizados (backfill): {backfill_count}")
                EmailAccountRepository.update_sync_status(gaccount_id, 'success')
            except Exception as e:
                error_msg = str(e)
                logger.error(f"  - ✗ Error Gmail {gaccount_email}: {error_msg}")
                EmailAccountRepository.update_sync_status(gaccount_id, 'error', error_msg)

        logger.info("=" * 60)
        logger.info(f"Gmail completado. Total guardados: {total_codes_saved}")
        logger.info("=" * 60)
    except Exception as e:
        logger.error(f"Error fatal: {e}", exc_info=True)
        sys.exit(1)
    finally:
        Database.close_connections()


if __name__ == '__main__':
    main()
