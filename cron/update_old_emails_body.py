#!/usr/bin/env python3
"""
Script para actualizar el campo email_body de correos antiguos
Lee los emails del servidor IMAP y actualiza los registros en la base de datos
"""

import sys
import os
import logging
from datetime import datetime

# Agregar el directorio padre al path para importar módulos
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from cron.database import Database
from cron.imap_service import ImapService
from cron.repositories import EmailAccountRepository

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('logs/update_old_emails.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)


def update_old_emails_body(limit=500):
    """
    Actualizar email_body de correos antiguos
    
    Args:
        limit: Número máximo de emails a procesar del servidor IMAP
    """
    logger.info("=" * 60)
    logger.info("Iniciando actualización de email_body para correos antiguos")
    logger.info("=" * 60)
    
    try:
        # Obtener cuenta maestra
        accounts = EmailAccountRepository.find_by_type('imap')
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
        
        if not master_account:
            for account in accounts:
                if account['email'] == 'streaming@pocoyoni.com':
                    master_account = account
                    break
        
        if not master_account:
            logger.error("No se encontró cuenta maestra IMAP")
            return
        
        logger.info(f"Usando cuenta maestra: {master_account['email']}")
        
        # Conectar a IMAP y leer emails
        imap_service = ImapService()
        emails = imap_service.read_account(master_account)
        
        # Limitar cantidad de emails
        if len(emails) > limit:
            emails = emails[:limit]
            logger.info(f"Limitando a {limit} emails")
        
        logger.info(f"Emails leídos del servidor: {len(emails)}")
        
        # Conectar a base de datos
        db = Database.get_connection()
        cursor = db.cursor()
        
        updated_count = 0
        not_found_count = 0
        
        # Para cada email, buscar el código correspondiente y actualizar
        for email_data in emails:
            try:
                # Buscar código por subject, from, date y recipient
                subject = email_data.get('subject', '')
                email_from = email_data.get('from', '')
                date = email_data.get('date', '')
                recipient = email_data.get('to_primary', '')
                
                # Obtener cuerpo del email (preferir HTML, sino texto)
                email_body = email_data.get('body_html') or email_data.get('body_text') or email_data.get('body') or ''
                
                if not email_body:
                    continue
                
                # Buscar código en la base de datos
                # Buscar por subject, from y recipient (más flexible que por fecha exacta)
                cursor.execute("""
                    SELECT id, email_body
                    FROM codes
                    WHERE subject = %s
                      AND email_from = %s
                      AND recipient_email = %s
                      AND (email_body IS NULL OR email_body = '')
                    LIMIT 1
                """, (subject, email_from, recipient.lower()))
                
                result = cursor.fetchone()
                
                if result:
                    code_id = result[0]
                    current_body = result[1]
                    
                    # Actualizar email_body
                    cursor.execute("""
                        UPDATE codes
                        SET email_body = %s
                        WHERE id = %s
                    """, (email_body, code_id))
                    
                    db.commit()
                    updated_count += 1
                    logger.info(f"  ✓ Actualizado código ID {code_id} - Subject: {subject[:50]}")
                else:
                    not_found_count += 1
                    if not_found_count <= 10:  # Solo mostrar los primeros 10
                        logger.debug(f"  - No encontrado: {subject[:50]}")
            
            except Exception as e:
                logger.error(f"Error al procesar email: {e}")
                db.rollback()
                continue
        
        cursor.close()
        
        logger.info("=" * 60)
        logger.info(f"Proceso completado:")
        logger.info(f"  - Códigos actualizados: {updated_count}")
        logger.info(f"  - Emails no encontrados en BD: {not_found_count}")
        logger.info(f"  - Total emails procesados: {len(emails)}")
        logger.info("=" * 60)
        
    except Exception as e:
        logger.error(f"Error fatal: {e}", exc_info=True)
    finally:
        Database.close_connections()


if __name__ == '__main__':
    # Permitir pasar el límite como argumento
    limit = int(sys.argv[1]) if len(sys.argv) > 1 else 500
    update_old_emails_body(limit=limit)
