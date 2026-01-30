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
from cron.repositories import EmailAccountRepository, PlatformRepository, CodeRepository, SettingsRepository
from cron.imap_service import ImapService
from cron.email_filter import EmailFilterService
from cron.code_extractor import CodeExtractorService

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


def main():
    """Función principal"""
    logger.info("=" * 60)
    logger.info("Iniciando lectura automática de emails")
    logger.info("=" * 60)
    
    if not CRON_CONFIG['enabled']:
        logger.warning("Cron jobs deshabilitados en configuración")
        return
    
    try:
        # Inicializar servicios
        imap_service = ImapService()
        filter_service = EmailFilterService()
        extractor_service = CodeExtractorService()
        
        # Obtener TODAS las cuentas IMAP habilitadas (no solo la maestra)
        # Así se leen correos enviados a casa2025, cineclub017, etc. en sus propios buzones
        accounts = EmailAccountRepository.find_by_type('imap')
        
        if not accounts:
            logger.info("No hay cuentas IMAP configuradas o habilitadas")
            return
        
        logger.info(f"Cuentas IMAP a procesar: {len(accounts)}")
        
        total_codes_saved = 0
        
        for account in accounts:
            account_id = account['id']
            account_email = account['email']
            
            logger.info("-" * 50)
            logger.info(f"Procesando cuenta: {account_email} (ID: {account_id})")
            
            try:
                # Leer emails de ESTA cuenta (cada una tiene su propio buzón)
                emails = imap_service.read_account(account)
                logger.info(f"  - Emails leídos: {len(emails)}")
                
                if not emails:
                    EmailAccountRepository.update_sync_status(account_id, 'success')
                    continue
                
                # Filtrar por asunto (Disney, Netflix, etc.)
                filtered_emails = filter_service.filter_by_subject(emails)
                logger.info(f"  - Emails filtrados: {len(filtered_emails)}")
                
                if not filtered_emails:
                    EmailAccountRepository.update_sync_status(account_id, 'success')
                    continue
                
                # Extraer códigos
                codes = extractor_service.extract_codes(filtered_emails)
                logger.info(f"  - Códigos extraídos: {len(codes)}")
                
                if not codes:
                    EmailAccountRepository.update_sync_status(account_id, 'success')
                    continue
                
                # Guardar códigos (recipient = destinatario del email, para la consulta)
                codes_saved = 0
                for code_data in codes:
                    platform = code_data['platform']
                    
                    platform_obj = PlatformRepository.find_by_name(platform)
                    if not platform_obj:
                        logger.warning(f"  - Plataforma '{platform}' no encontrada, saltando")
                        continue
                    
                    if not platform_obj['enabled']:
                        continue
                    
                    # Destinatario: quien recibió el correo (para Consulta tu Código)
                    recipient_email = code_data.get('to_primary', '') or (code_data.get('to', [])[0] if code_data.get('to') else '')
                    if not recipient_email:
                        recipient_email = account_email  # Fallback: la propia cuenta
                    
                    if CodeRepository.code_exists(
                        code_data['code'],
                        platform_obj['id'],
                        account_id,
                        recipient_email
                    ):
                        logger.info(f"  - Código duplicado: {code_data['code']} para {recipient_email}")
                        continue
                    
                    email_body = code_data.get('body_html') or code_data.get('body_text') or code_data.get('body') or ''
                    
                    save_data = {
                        'email_account_id': account_id,
                        'platform_id': platform_obj['id'],
                        'code': code_data['code'],
                        'email_from': code_data.get('from'),
                        'subject': code_data.get('subject'),
                        'email_body': email_body,
                        'received_at': code_data.get('date'),
                        'origin': 'imap',
                        'recipient_email': recipient_email.lower() if recipient_email else account_email
                    }
                    
                    code_id = CodeRepository.save(save_data)
                    
                    if code_id:
                        codes_saved += 1
                        logger.info(f"  - ✓ Código guardado: {code_data['code']} ({platform}) para {recipient_email}")
                    else:
                        logger.error(f"  - ✗ Error al guardar: {code_data['code']}")
                
                total_codes_saved += codes_saved
                logger.info(f"  - Códigos guardados en esta cuenta: {codes_saved}")
                EmailAccountRepository.update_sync_status(account_id, 'success')
                
            except Exception as e:
                error_msg = str(e)
                logger.error(f"  - ✗ Error al procesar {account_email}: {error_msg}")
                EmailAccountRepository.update_sync_status(account_id, 'error', error_msg)
        
        logger.info("=" * 60)
        logger.info(f"Proceso completado. Total de códigos guardados: {total_codes_saved}")
        logger.info("=" * 60)
        
    except Exception as e:
        logger.error(f"Error fatal en proceso de lectura: {e}", exc_info=True)
        sys.exit(1)
    finally:
        # Cerrar conexiones
        Database.close_connections()


if __name__ == '__main__':
    main()