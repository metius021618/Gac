#!/usr/bin/env python3
"""
GAC - Script Principal de Lectura de Emails
Cron Job para leer emails automáticamente y extraer códigos
"""

import sys
import os
import logging
from datetime import datetime

# Agregar directorio padre al path para que encuentre el módulo cron
script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)

# Cambiar al directorio del script para que los imports funcionen
os.chdir(script_dir)

from cron.config import CRON_CONFIG, LOG_CONFIG
from cron.database import Database
from cron.repositories import EmailAccountRepository, PlatformRepository, CodeRepository
from cron.imap_service import ImapService
from cron.email_filter import EmailFilterService
try:
    from cron.gmail_service import GmailService
    GMAIL_SERVICE_AVAILABLE = True
except Exception:
    GmailService = None
    GMAIL_SERVICE_AVAILABLE = False

# Configurar logging
logging.basicConfig(
    level=getattr(logging, LOG_CONFIG['level'].upper(), logging.INFO),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_CONFIG['file']),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)


def _backfill_email_bodies(emails, limit=500):
    """
    Actualizar email_body de códigos ya guardados que lo tengan vacío.
    Usa la misma lista de emails ya leída por el cron (un solo cron hace todo).
    """
    if not emails:
        return 0
    updated = 0
    try:
        db = Database.get_connection()
        cursor = db.cursor()
        # Limitar para no sobrecargar en cada ejecución
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
                    logger.info(f"  - ✓ Cuerpo actualizado para código ID {code_id}")
            except Exception as e:
                logger.debug(f"  - Backfill skip: {e}")
                try:
                    db.rollback()
                except Exception:
                    pass
        cursor.close()
    except Exception as e:
        logger.warning(f"Backfill de cuerpos: {e}")
    return updated


def main():
    """Función principal"""
    logger.info("=" * 60)
    logger.info("Iniciando lectura automática de emails")
    logger.info("=" * 60)
    
    if not CRON_CONFIG['enabled']:
        logger.warning("Cron jobs deshabilitados en configuración")
        return
    
    try:
        # Inicializar servicios (solo IMAP y filtro por DE/plataforma; no extracción de código numérico)
        imap_service = ImapService()
        filter_service = EmailFilterService()
        
        # Obtener cuenta maestra IMAP
        # Buscar cuenta con is_master = true en provider_config
        accounts = EmailAccountRepository.find_by_type('imap')
        
        # Buscar cuenta maestra
        master_account = None
        for account in accounts:
            try:
                import json
                config = json.loads(account.get('provider_config', '{}'))
                if config.get('is_master', False):
                    master_account = account
                    break
            except:
                continue
        
        # Si no hay cuenta maestra, usar la primera cuenta o buscar streaming@pocoyoni.com
        if not master_account:
            for account in accounts:
                if account['email'] == 'streaming@pocoyoni.com':
                    master_account = account
                    break
        
        total_codes_saved = 0
        
        if not master_account:
            logger.info("No se encontró cuenta maestra IMAP; se procesarán solo cuentas Gmail si hay.")
        else:
            logger.info(f"Procesando cuenta maestra: {master_account['email']}")
        
        # Procesar cuenta maestra IMAP (si existe)
        account = master_account
        if account:
            account_id = account['id']
            account_email = account['email']
            logger.info(f"Procesando cuenta IMAP: {account_email} (ID: {account_id})")
        else:
            account_id = None
            account_email = None
        
        if account:
            try:
                # Leer emails
                emails = imap_service.read_account(account)
                logger.info(f"  - Emails leídos: {len(emails)}")
                if not emails:
                    EmailAccountRepository.update_sync_status(account_id, 'success')
                else:
                    # Filtrar por asunto o por DE (plataforma: Disney+, Netflix, etc.)
                    filtered_emails = filter_service.filter_by_subject(emails)
                    logger.info(f"  - Emails filtrados: {len(filtered_emails)}")
                    
                    if not filtered_emails:
                        EmailAccountRepository.update_sync_status(account_id, 'success')
                    else:
                        # Guardar cada correo: DE → code, destinatario → recipient_email, fecha → received_at, HTML → email_body
                        records_saved = 0
                        for email_data in filtered_emails:
                            platform = email_data.get('matched_platform')
                            if not platform:
                                continue
                            platform_obj = PlatformRepository.find_by_name(platform)
                            if not platform_obj:
                                logger.warning(f"  - Plataforma '{platform}' no encontrada, saltando")
                                continue
                            if not platform_obj['enabled']:
                                continue
                            email_from = email_data.get('from', '') or ''
                            recipient_email = (email_data.get('to_primary', '') or (email_data.get('to', [None])[0] or '')).strip().lower()
                            if not recipient_email:
                                logger.warning(f"  - Email sin destinatario, saltando (asunto: {email_data.get('subject', '')[:40]})")
                                continue
                            received_at = email_data.get('date', '')
                            subject = email_data.get('subject', '')
                            email_body = email_data.get('body_html') or email_data.get('body_text') or email_data.get('body') or ''
                            if CodeRepository.email_record_exists(account_id, email_from, recipient_email, subject, received_at):
                                if email_body and CodeRepository.update_email_body_by_email(
                                    account_id, email_from, recipient_email, subject, received_at, email_body
                                ):
                                    logger.info(f"  - ✓ Cuerpo actualizado para email ya registrado ({recipient_email})")
                                continue
                            save_data = {
                                'email_account_id': account_id,
                                'platform_id': platform_obj['id'],
                                'code': email_from,
                                'email_from': email_from,
                                'subject': subject,
                                'email_body': email_body,
                                'received_at': received_at,
                                'origin': 'imap',
                                'recipient_email': recipient_email,
                            }
                            code_id = CodeRepository.save(save_data)
                            if code_id:
                                records_saved += 1
                                logger.info(f"  - ✓ Correo guardado: DE={email_from[:40]} → {recipient_email}")
                            else:
                                logger.error(f"  - ✗ Error al guardar correo para {recipient_email}")
                        
                        total_codes_saved += records_saved
                        logger.info(f"  - Correos guardados en esta cuenta: {records_saved}")
                        
                        # Un solo cron: rellenar cuerpos de códigos antiguos que tengan email_body vacío
                        backfill_count = _backfill_email_bodies(emails, limit=300)
                        if backfill_count:
                            logger.info(f"  - Cuerpos de email actualizados (backfill): {backfill_count}")
                    
                    # Actualizar estado de sincronización
                    EmailAccountRepository.update_sync_status(account_id, 'success')
            
            except Exception as e:
                error_msg = str(e)
                logger.error(f"  - ✗ Error al procesar cuenta {account_email}: {error_msg}")
                EmailAccountRepository.update_sync_status(account_id, 'error', error_msg)
        
        # --- Procesar cuentas Gmail (destinatario = la propia cuenta Gmail) ---
        if GMAIL_SERVICE_AVAILABLE and GmailService:
            gmail_accounts = EmailAccountRepository.find_by_type('gmail')
            for gaccount in gmail_accounts:
                gaccount_id = gaccount['id']
                gaccount_email = (gaccount.get('email') or '').strip().lower()
                logger.info(f"Procesando cuenta Gmail: {gaccount_email} (ID: {gaccount_id})")
                try:
                    gmail_service = GmailService()
                    emails = gmail_service.read_account(gaccount, max_messages=200)
                    logger.info(f"  - Emails leídos (Gmail): {len(emails)}")
                    if not emails:
                        EmailAccountRepository.update_sync_status(gaccount_id, 'success')
                        continue
                    filtered = filter_service.filter_by_subject(emails)
                    logger.info(f"  - Emails filtrados (Gmail): {len(filtered)}")
                    if not filtered:
                        EmailAccountRepository.update_sync_status(gaccount_id, 'success')
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
                        recipient_email = (email_data.get('to_primary') or gaccount_email).strip().lower()
                        received_at = email_data.get('date', '')
                        subject = email_data.get('subject', '')
                        email_body = email_data.get('body_html') or email_data.get('body_text') or email_data.get('body') or ''
                        if CodeRepository.email_record_exists(gaccount_id, email_from, recipient_email, subject, received_at):
                            if email_body and CodeRepository.update_email_body_by_email(
                                gaccount_id, email_from, recipient_email, subject, received_at, email_body
                            ):
                                logger.info(f"  - ✓ Cuerpo actualizado (Gmail) para {recipient_email}")
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
                            logger.info(f"  - ✓ Correo Gmail guardado: DE={email_from[:40]} → {recipient_email}")
                    total_codes_saved += records_saved
                    backfill_count = _backfill_email_bodies(emails, limit=100)
                    if backfill_count:
                        logger.info(f"  - Cuerpos actualizados (backfill Gmail): {backfill_count}")
                    EmailAccountRepository.update_sync_status(gaccount_id, 'success')
                except Exception as e:
                    error_msg = str(e)
                    logger.error(f"  - ✗ Error al procesar Gmail {gaccount_email}: {error_msg}")
                    EmailAccountRepository.update_sync_status(gaccount_id, 'error', error_msg)
        
        logger.info("=" * 60)
        logger.info(f"Proceso completado. Total de correos guardados: {total_codes_saved}")
        logger.info("=" * 60)
        
    except Exception as e:
        logger.error(f"Error fatal en proceso de lectura: {e}", exc_info=True)
        sys.exit(1)
    finally:
        # Cerrar conexiones
        Database.close_connections()


if __name__ == '__main__':
    main()