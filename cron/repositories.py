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
        """Obtener asuntos de email para una plataforma"""
        try:
            db = Database.get_connection()
            if USE_PYMYSQL:
                cursor = db.cursor(DictCursor)
            else:
                cursor = db.cursor(dictionary=True)
            
            platform_upper = platform.upper()
            pattern = f"{platform_upper}_%"
            
            cursor.execute("""
                SELECT name, value
                FROM settings
                WHERE name LIKE %s
                ORDER BY name ASC
            """, (pattern,))
            
            results = cursor.fetchall()
            cursor.close()
            
            # Filtrar solo los que son asuntos (_1, _2, _3, _4)
            subjects = []
            for result in results:
                if result['name'].endswith(('_1', '_2', '_3', '_4')):
                    subjects.append(result['value'])
            
            return subjects
        except Error as e:
            logger.error(f"Error al obtener asuntos para plataforma '{platform}': {e}")
            return []
    
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
                code_data.get('received_at'),
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
    def code_exists(code, platform_id, email_account_id, recipient_email=None):
        """Verificar si código existe para esta combinación"""
        try:
            db = Database.get_connection()
            cursor = db.cursor()
            
            # Si se proporciona recipient_email, verificar duplicados incluyendo ese campo
            if recipient_email:
                cursor.execute("""
                    SELECT COUNT(*) as count
                    FROM codes
                    WHERE code = %s
                      AND platform_id = %s
                      AND email_account_id = %s
                      AND recipient_email = %s
                      AND status = 'available'
                """, (code, platform_id, email_account_id, recipient_email.lower()))
            else:
                # Si no se proporciona, verificar solo por código, plataforma y cuenta
                cursor.execute("""
                    SELECT COUNT(*) as count
                    FROM codes
                    WHERE code = %s
                      AND platform_id = %s
                      AND email_account_id = %s
                      AND status = 'available'
                """, (code, platform_id, email_account_id))
            
            result = cursor.fetchone()
            cursor.close()
            
            return result[0] > 0 if result else False
        except Error as e:
            logger.error(f"Error al verificar código duplicado: {e}")
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