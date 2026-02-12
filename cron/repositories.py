"""
GAC - Repositorios para Scripts Python
"""

import logging
from cron.database import Database, USE_PYMYSQL

# Compatibilidad con PyMySQL y mysql-connector
if USE_PYMYSQL:
    import pymysql.cursors
    DictCursor = pymysql.cursors.DictCursor
    Error = Exception
else:
    from mysql.connector import Error
    DictCursor = None  # mysql-connector usa dictionary=True

logger = logging.getLogger(__name__)


class EmailAccountRepository:
    """Repositorio de cuentas de email"""
    
    @staticmethod
    def find_all_enabled():
        """Obtener todas las cuentas de email habilitadas"""
        try:
            db = Database.get_connection()
            if USE_PYMYSQL:
                cursor = db.cursor(DictCursor)
            else:
                cursor = db.cursor(dictionary=True)
            
            cursor.execute("""
                SELECT 
                    id,
                    email,
                    type,
                    provider_config,
                    oauth_token,
                    oauth_refresh_token,
                    enabled,
                    last_sync_at,
                    sync_status,
                    error_message
                FROM email_accounts
                WHERE enabled = 1
                ORDER BY created_at ASC
            """)
            
            accounts = cursor.fetchall()
            cursor.close()
            
            return accounts
        except Error as e:
            logger.error(f"Error al obtener cuentas de email: {e}")
            return []
    
    @staticmethod
    def find_by_type(account_type):
        """Obtener cuentas por tipo"""
        try:
            db = Database.get_connection()
            if USE_PYMYSQL:
                cursor = db.cursor(DictCursor)
            else:
                cursor = db.cursor(dictionary=True)
            
            cursor.execute("""
                SELECT 
                    id,
                    email,
                    type,
                    provider_config,
                    oauth_token,
                    oauth_refresh_token,
                    enabled,
                    last_sync_at,
                    sync_status,
                    error_message
                FROM email_accounts
                WHERE type = %s AND enabled = 1
                ORDER BY created_at ASC
            """, (account_type,))
            
            accounts = cursor.fetchall()
            cursor.close()
            
            return accounts
        except Error as e:
            logger.error(f"Error al obtener cuentas por tipo: {e}")
            return []

    @staticmethod
    def get_gmail_matrix_account():
        """Obtener la cuenta Gmail matriz desde la tabla gmail_matrix (única que lee el lector)."""
        try:
            db = Database.get_connection()
            if USE_PYMYSQL:
                cursor = db.cursor(DictCursor)
            else:
                cursor = db.cursor(dictionary=True)
            cursor.execute("""
                SELECT ea.id, ea.email, ea.type, ea.provider_config,
                       ea.oauth_token, ea.oauth_refresh_token, ea.enabled,
                       ea.last_sync_at, ea.sync_status, ea.error_message
                FROM gmail_matrix gm
                INNER JOIN email_accounts ea ON ea.id = gm.email_account_id AND ea.enabled = 1
                WHERE gm.id = 1
                LIMIT 1
            """)
            row = cursor.fetchone()
            cursor.close()
            return row
        except Exception as e:
            logger.error(f"Error al obtener cuenta Gmail matriz: {e}")
            return None

    @staticmethod
    def update_sync_status(account_id, status, error_message=None):
        """Actualizar estado de sincronización"""
        try:
            db = Database.get_connection()
            cursor = db.cursor()
            
            cursor.execute("""
                UPDATE email_accounts
                SET last_sync_at = NOW(),
                    sync_status = %s,
                    error_message = %s,
                    updated_at = NOW()
                WHERE id = %s
            """, (status, error_message, account_id))
            
            db.commit()
            cursor.close()
            
            return True
        except Error as e:
            logger.error(f"Error al actualizar estado de sincronización: {e}")
            db.rollback()
            return False

    @staticmethod
    def update_oauth_tokens(account_id, access_token, refresh_token=None):
        """Actualizar access_token y opcionalmente refresh_token de una cuenta (Outlook/Gmail)."""
        try:
            db = Database.get_connection()
            cursor = db.cursor()
            if refresh_token is not None:
                cursor.execute("""
                    UPDATE email_accounts
                    SET oauth_token = %s, oauth_refresh_token = %s, error_message = NULL, updated_at = NOW()
                    WHERE id = %s
                """, (access_token, refresh_token, account_id))
            else:
                cursor.execute("""
                    UPDATE email_accounts
                    SET oauth_token = %s, error_message = NULL, updated_at = NOW()
                    WHERE id = %s
                """, (access_token, account_id))
            db.commit()
            cursor.close()
            return True
        except Error as e:
            logger.error(f"Error al actualizar tokens OAuth: {e}")
            db.rollback()
            return False


class EmailSubjectRepository:
    """Repositorio de asuntos de email (tabla email_subjects). Fuente única para filtrar correos en Gmail, Outlook e IMAP."""

    @staticmethod
    def get_all_subjects_by_platform():
        """
        Obtener todos los asuntos activos por plataforma (solo plataformas habilitadas).
        Devuelve dict: { 'netflix': ['asunto1', 'asunto2'], 'disney': [...], ... }
        Usado por EmailFilterService para filtrar correos SOLO por los asuntos definidos en la página de asuntos.
        """
        try:
            db = Database.get_connection()
            if USE_PYMYSQL:
                cursor = db.cursor(DictCursor)
            else:
                cursor = db.cursor(dictionary=True)
            cursor.execute("""
                SELECT p.name AS platform_name, es.subject_line
                FROM email_subjects es
                INNER JOIN platforms p ON es.platform_id = p.id
                WHERE es.active = 1 AND p.enabled = 1
                ORDER BY p.name, es.subject_line
            """)
            rows = cursor.fetchall()
            cursor.close()
            result = {}
            for row in rows:
                name = (row.get('platform_name') or '').strip().lower()
                if not name:
                    continue
                line = (row.get('subject_line') or '').strip()
                if not line:
                    continue
                if name not in result:
                    result[name] = []
                result[name].append(line)
            return result
        except Error as e:
            logger.error(f"Error al obtener asuntos desde email_subjects: {e}")
            return {}

    @staticmethod
    def get_subjects_for_platform(platform_name):
        """Obtener lista de asuntos para una plataforma por nombre (compatibilidad)."""
        all_subjects = EmailSubjectRepository.get_all_subjects_by_platform()
        return all_subjects.get((platform_name or '').strip().lower(), [])


class PlatformRepository:
    """Repositorio de plataformas"""

    @staticmethod
    def find_by_name(name):
        """Obtener plataforma por nombre"""
        try:
            db = Database.get_connection()
            if USE_PYMYSQL:
                cursor = db.cursor(DictCursor)
            else:
                cursor = db.cursor(dictionary=True)
            
            cursor.execute("""
                SELECT 
                    id,
                    name,
                    display_name,
                    enabled,
                    config
                FROM platforms
                WHERE name = %s
            """, (name,))
            
            platform = cursor.fetchone()
            cursor.close()
            
            return platform
        except Error as e:
            logger.error(f"Error al obtener plataforma por nombre: {e}")
            return None


class SettingsRepository:
    """Repositorio de settings"""
    
    @staticmethod
    def get(name, default=None):
        """Obtener un setting"""
        try:
            db = Database.get_connection()
            if USE_PYMYSQL:
                cursor = db.cursor(DictCursor)
            else:
                cursor = db.cursor(dictionary=True)
            
            cursor.execute("""
                SELECT value, type
                FROM settings
                WHERE name = %s
            """, (name,))
            
            result = cursor.fetchone()
            cursor.close()
            
            if result:
                value = SettingsRepository._cast_value(result['value'], result.get('type', 'string'))
                return value
            
            return default
        except Error as e:
            logger.error(f"Error al obtener setting '{name}': {e}")
            return default

    @staticmethod
    def get_email_subjects_for_platform(platform):
        """Obtener asuntos para una plataforma desde tabla email_subjects (misma fuente que la página de asuntos)."""
        return EmailSubjectRepository.get_subjects_for_platform(platform)

    @staticmethod
    def is_platform_enabled(platform):
        """Verificar si plataforma está habilitada"""
        platform_upper = platform.upper()
        setting_name = f"HABILITAR_{platform_upper}"
        
        value = SettingsRepository.get(setting_name, '0')
        return str(value).lower() in ('1', 'true', 'yes')
    
    @staticmethod
    def _cast_value(value, value_type):
        """Convertir valor según tipo"""
        if value_type == 'boolean' or value_type == 'bool':
            return str(value).lower() in ('1', 'true', 'yes')
        elif value_type == 'integer' or value_type == 'int':
            return int(value)
        elif value_type == 'float' or value_type == 'double':
            return float(value)
        else:
            return value


class CodeRepository:
    """Repositorio de códigos"""
    
    @staticmethod
    def save(code_data):
        """Guardar código nuevo"""
        try:
            db = Database.get_connection()
            cursor = db.cursor()
            received_at = code_data.get('received_at') or ''
            if not received_at or not str(received_at).strip():
                from datetime import datetime
                received_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cursor.execute("""
                INSERT INTO codes (
                    email_account_id,
                    platform_id,
                    code,
                    email_from,
                    subject,
                    email_body,
                    received_at,
                    origin,
                    status,
                    recipient_email
                ) VALUES (
                    %s, %s, %s, %s, %s, %s, %s, %s, 'available', %s
                )
            """, (
                code_data['email_account_id'],
                code_data['platform_id'],
                code_data['code'],
                code_data.get('email_from'),
                code_data.get('subject'),
                code_data.get('email_body'),  # Cuerpo del email (HTML o texto)
                received_at,
                code_data.get('origin', 'imap'),
                code_data.get('recipient_email')
            ))
            
            code_id = cursor.lastrowid
            db.commit()
            cursor.close()
            
            return code_id
        except Error as e:
            logger.error(f"Error al guardar código: {e}")
            db.rollback()
            return None
    
    @staticmethod
    def email_record_exists(email_account_id, email_from, recipient_email, subject, received_at):
        """Verificar si ya existe un registro para este email (DE, destinatario, asunto, fecha)."""
        if not recipient_email or not subject:
            return False
        try:
            db = Database.get_connection()
            cursor = db.cursor()
            cursor.execute("""
                SELECT 1 FROM codes
                WHERE email_account_id = %s
                  AND email_from = %s
                  AND LOWER(recipient_email) = LOWER(%s)
                  AND subject = %s
                  AND received_at = %s
                LIMIT 1
            """, (email_account_id, email_from or '', recipient_email, subject, received_at or ''))
            row = cursor.fetchone()
            cursor.close()
            return row is not None
        except Error as e:
            logger.error(f"Error al verificar email existente: {e}")
            return False

    @staticmethod
    def update_email_body_by_email(email_account_id, email_from, recipient_email, subject, received_at, email_body):
        """Actualizar email_body del registro que coincida con DE, destinatario, asunto y fecha."""
        if not email_body or not recipient_email or not subject:
            return False
        try:
            db = Database.get_connection()
            cursor = db.cursor()
            cursor.execute("""
                UPDATE codes
                SET email_body = %s
                WHERE email_account_id = %s AND email_from = %s
                  AND LOWER(recipient_email) = LOWER(%s)
                  AND subject = %s AND received_at = %s
                  AND (email_body IS NULL OR email_body = '')
                LIMIT 1
            """, (email_body, email_account_id, email_from or '', recipient_email, subject, received_at or ''))
            updated = cursor.rowcount > 0
            db.commit()
            cursor.close()
            return updated
        except Error as e:
            logger.error(f"Error al actualizar email_body: {e}")
            try:
                db.rollback()
            except Exception:
                pass
            return False
    
    @staticmethod
    def save_to_warehouse(code_data):
        """Guardar código en warehouse"""
        try:
            db = Database.get_warehouse_connection()
            cursor = db.cursor()
            
            cursor.execute("""
                INSERT INTO codes_history (
                    code_id,
                    email_account_id,
                    platform_id,
                    code,
                    email_from,
                    subject,
                    received_at,
                    consumed_at,
                    origin
                ) VALUES (
                    %s, %s, %s, %s, %s, %s, %s, %s, %s
                )
            """, (
                code_data.get('id'),
                code_data['email_account_id'],
                code_data['platform_id'],
                code_data['code'],
                code_data.get('email_from'),
                code_data.get('subject'),
                code_data.get('received_at'),
                code_data.get('consumed_at'),
                code_data.get('origin', 'imap')
            ))
            
            db.commit()
            cursor.close()
            
            return True
        except Error as e:
            logger.error(f"Error al guardar código en warehouse: {e}")
            db.rollback()
            return False