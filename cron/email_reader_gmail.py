#!/usr/bin/env python3
"""
GAC - Lector de correos SOLO Gmail (Gmail API)
Script independiente: solo lee cuentas type=gmail y guarda en codes con origin='gmail'.
No mezcla con IMAP. Para consultas con @gmail.com se ejecuta este script.
Cuando Google devuelve 429 (rate limit), se guarda Retry-After y no se llama a la API
hasta esa hora; el bucle del cron sigue corriendo (solo se salta esta llamada).
"""

import sys
import os
import re
import time
import logging
import calendar
from datetime import datetime

script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)
os.chdir(script_dir)

from cron.config import CRON_CONFIG, LOG_CONFIG
from cron.database import Database
from cron.repositories import EmailAccountRepository, PlatformRepository, CodeRepository
from cron.email_filter import EmailFilterService
from cron.gmail_service import GmailService

logging.basicConfig(
    level=getattr(logging, LOG_CONFIG['level'].upper(), logging.INFO),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_CONFIG['file']),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Archivo para cooldown 429: no llamar a Gmail API hasta este timestamp (epoch UTC)
GMAIL_RATE_LIMIT_FILE = os.path.join(parent_dir, 'logs', 'gmail_rate_limit_until.txt')


def _parse_retry_after_429(error_msg):
    """Extrae 'Retry after 2026-02-16T01:38:14.902Z' del mensaje y devuelve epoch UTC o None."""
    if not error_msg:
        return None
    m = re.search(r'Retry after (\d{4}-\d{2}-\d{2}T[\d.:]+Z)', str(error_msg))
    if not m:
        return None
    ts_str = m.group(1).strip()
    try:
        dt = datetime.strptime(ts_str[:19], '%Y-%m-%dT%H:%M:%S')
        epoch = calendar.timegm(dt.timetuple())
        if len(ts_str) > 19 and ts_str[19] == '.':
            frac = float(ts_str[19:].replace('Z', '').strip() or '0')
            epoch += frac
        return epoch
    except Exception:
        return None


def _is_gmail_in_cooldown():
    """True si debemos saltar la llamada a Gmail por 429. (until_iso opcional para el log)."""
    try:
        if not os.path.isfile(GMAIL_RATE_LIMIT_FILE):
            return False, None
        with open(GMAIL_RATE_LIMIT_FILE, 'r') as f:
            line = (f.read() or '').strip()
        if not line:
            return False, None
        until_epoch = float(line)
        if time.time() < until_epoch:
            until_iso = datetime.utcfromtimestamp(until_epoch).strftime('%Y-%m-%d %H:%M:%S UTC')
            return True, until_iso
        return False, None
    except Exception:
        return False, None


def _set_gmail_cooldown_until(until_epoch):
    """Guarda hasta cuándo no llamar a la API (por 429)."""
    try:
        log_dir = os.path.dirname(GMAIL_RATE_LIMIT_FILE)
        if not os.path.isdir(log_dir):
            os.makedirs(log_dir, exist_ok=True)
        with open(GMAIL_RATE_LIMIT_FILE, 'w') as f:
            f.write(str(until_epoch))
    except Exception as e:
        logger.warning("No se pudo guardar cooldown Gmail: %s", e)


def _clear_gmail_cooldown():
    """Quita el cooldown (al empezar una ejecución normal)."""
    try:
        if os.path.isfile(GMAIL_RATE_LIMIT_FILE):
            os.remove(GMAIL_RATE_LIMIT_FILE)
    except Exception:
        pass


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

    in_cooldown, until_iso = _is_gmail_in_cooldown()
    if in_cooldown:
        logger.info(
            "Gmail en pausa por rate limit (429) hasta %s. No se llama a la API; el bucle sigue. "
            "IMAP y Outlook se ejecutan con normalidad.", until_iso
        )
        return

    _clear_gmail_cooldown()
    try:
        filter_service = EmailFilterService()
        gaccount = EmailAccountRepository.get_gmail_matrix_account()
        if not gaccount:
            logger.info("No hay cuenta Gmail matriz configurada (tabla gmail_matrix).")
            return

        gmail_accounts = [gaccount]
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
                # Si es 429 (rate limit), guardar Retry-After para no volver a llamar hasta esa hora
                if '429' in error_msg or 'rateLimitExceeded' in error_msg.lower():
                    until_epoch = _parse_retry_after_429(error_msg)
                    if until_epoch:
                        _set_gmail_cooldown_until(until_epoch)
                        until_iso = datetime.utcfromtimestamp(until_epoch).strftime('%Y-%m-%d %H:%M:%S UTC')
                        logger.info(
                            "Gmail 429: cooldown guardado hasta %s. En los próximos ciclos no se llamará a la API; "
                            "el bucle y el resto de lectores siguen.", until_iso
                        )

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
