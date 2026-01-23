"""
GAC - Módulo de Base de Datos para Scripts Python
Compatible con Python 3.6 usando PyMySQL
"""

try:
    import pymysql
    pymysql.install_as_MySQLdb()
    USE_PYMYSQL = True
except ImportError:
    try:
        import mysql.connector
        from mysql.connector import Error
        USE_PYMYSQL = False
    except ImportError:
        raise ImportError("Se requiere PyMySQL o mysql-connector-python")

from cron.config import DB_CONFIG, WAREHOUSE_DB_CONFIG
import logging

logger = logging.getLogger(__name__)


class Database:
    """Maneja conexiones a las bases de datos"""
    
    _operational_connection = None
    _warehouse_connection = None
    
    @classmethod
    def get_connection(cls):
        """Obtener conexión a BD operativa"""
        if cls._operational_connection is None or not cls._is_connected(cls._operational_connection):
            try:
                if USE_PYMYSQL:
                    cls._operational_connection = pymysql.connect(
                        host=DB_CONFIG['host'],
                        port=DB_CONFIG['port'],
                        database=DB_CONFIG['database'],
                        user=DB_CONFIG['user'],
                        password=DB_CONFIG['password'],
                        charset='utf8mb4',
                        autocommit=False
                    )
                else:
                    cls._operational_connection = mysql.connector.connect(
                        host=DB_CONFIG['host'],
                        port=DB_CONFIG['port'],
                        database=DB_CONFIG['database'],
                        user=DB_CONFIG['user'],
                        password=DB_CONFIG['password'],
                        charset='utf8mb4',
                        collation='utf8mb4_spanish_ci',
                        autocommit=False
                    )
                logger.info("Conexión a BD operativa establecida")
            except Exception as e:
                logger.error(f"Error al conectar con BD operativa: {e}")
                raise
        
        return cls._operational_connection
    
    @classmethod
    def get_warehouse_connection(cls):
        """Obtener conexión a BD warehouse"""
        if cls._warehouse_connection is None or not cls._is_connected(cls._warehouse_connection):
            try:
                if USE_PYMYSQL:
                    cls._warehouse_connection = pymysql.connect(
                        host=WAREHOUSE_DB_CONFIG['host'],
                        port=WAREHOUSE_DB_CONFIG['port'],
                        database=WAREHOUSE_DB_CONFIG['database'],
                        user=WAREHOUSE_DB_CONFIG['user'],
                        password=WAREHOUSE_DB_CONFIG['password'],
                        charset='utf8mb4',
                        autocommit=False
                    )
                else:
                    cls._warehouse_connection = mysql.connector.connect(
                        host=WAREHOUSE_DB_CONFIG['host'],
                        port=WAREHOUSE_DB_CONFIG['port'],
                        database=WAREHOUSE_DB_CONFIG['database'],
                        user=WAREHOUSE_DB_CONFIG['user'],
                        password=WAREHOUSE_DB_CONFIG['password'],
                        charset='utf8mb4',
                        collation='utf8mb4_spanish_ci',
                        autocommit=False
                    )
                logger.info("Conexión a BD warehouse establecida")
            except Exception as e:
                logger.error(f"Error al conectar con BD warehouse: {e}")
                raise
        
        return cls._warehouse_connection
    
    @classmethod
    def _is_connected(cls, conn):
        """Verificar si la conexión está activa"""
        if USE_PYMYSQL:
            try:
                conn.ping(reconnect=False)
                return True
            except:
                return False
        else:
            return conn.is_connected()
    
    @classmethod
    def close_connections(cls):
        """Cerrar todas las conexiones"""
        if cls._operational_connection:
            try:
                if USE_PYMYSQL:
                    if cls._is_connected(cls._operational_connection):
                        cls._operational_connection.close()
                else:
                    if cls._operational_connection.is_connected():
                        cls._operational_connection.close()
            except:
                pass
            cls._operational_connection = None
        
        if cls._warehouse_connection:
            try:
                if USE_PYMYSQL:
                    if cls._is_connected(cls._warehouse_connection):
                        cls._warehouse_connection.close()
                else:
                    if cls._warehouse_connection.is_connected():
                        cls._warehouse_connection.close()
            except:
                pass
            cls._warehouse_connection = None