"""
GAC - Configuración para Scripts Python (Cron Jobs)
"""

import os
from dotenv import load_dotenv

# Cargar variables de entorno
load_dotenv(os.path.join(os.path.dirname(__file__), '..', '.env'))

# Configuración de Base de Datos Operativa
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'database': os.getenv('DB_NAME', 'pocoavbb_gac'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', '')
}

# Configuración de Base de Datos Warehouse
WAREHOUSE_DB_CONFIG = {
    'host': os.getenv('WAREHOUSE_DB_HOST', 'localhost'),
    'port': int(os.getenv('WAREHOUSE_DB_PORT', 3306)),
    'database': os.getenv('WAREHOUSE_DB_NAME', 'pocoavbb_gac'),
    'user': os.getenv('WAREHOUSE_DB_USER', 'root'),
    'password': os.getenv('WAREHOUSE_DB_PASSWORD', '')
}

# Configuración Gmail API
GMAIL_CONFIG = {
    'client_id': os.getenv('GMAIL_CLIENT_ID', ''),
    'client_secret': os.getenv('GMAIL_CLIENT_SECRET', ''),
    'redirect_uri': os.getenv('GMAIL_REDIRECT_URI', ''),
    'scopes': [os.getenv('GMAIL_SCOPES', 'https://www.googleapis.com/auth/gmail.readonly')]
}

# Configuración Outlook/Microsoft Graph API
OUTLOOK_CONFIG = {
    'client_id': os.getenv('OUTLOOK_CLIENT_ID', ''),
    'client_secret': os.getenv('OUTLOOK_CLIENT_SECRET', ''),
    'tenant_id': os.getenv('OUTLOOK_TENANT_ID', ''),
    'redirect_uri': os.getenv('OUTLOOK_REDIRECT_URI', ''),
    'scopes': ['https://graph.microsoft.com/Mail.Read', 'offline_access']
}

# Configuración IMAP
IMAP_CONFIG = {
    'host': os.getenv('IMAP_HOST', ''),
    'port': int(os.getenv('IMAP_PORT', 993)),
    'encryption': os.getenv('IMAP_ENCRYPTION', 'ssl')
}

# Configuración de Cron
CRON_CONFIG = {
    'enabled': os.getenv('CRON_ENABLED', 'true').lower() == 'true',
    'email_reader_interval': int(os.getenv('CRON_EMAIL_READER_INTERVAL', 5)),
    'warehouse_sync_interval': int(os.getenv('CRON_WAREHOUSE_SYNC_INTERVAL', 60)),
    # Intervalo en segundos de espera entre ciclo y ciclo (sync_loop.py). Tras ejecutar, espera esto y vuelve a iniciar.
    # Para evitar 429 en Gmail API (límite por cuenta), se recomienda >= 30 (ej. CRON_READER_LOOP_SECONDS=30 o 60).
    'reader_loop_seconds': max(0.5, float(os.getenv('CRON_READER_LOOP_SECONDS', '30').replace(',', '.'))),
    # Lectura Gmail: solo últimos N mensajes y solo de los últimos N días (optimiza tiempo por ciclo).
    'gmail_max_messages': min(100, max(10, int(os.getenv('CRON_GMAIL_MAX_MESSAGES', 20)))),
    'gmail_newer_than_days': max(1, min(30, int(os.getenv('CRON_GMAIL_NEWER_THAN_DAYS', 1)))),
}

# Configuración de Logging
LOG_CONFIG = {
    'level': os.getenv('LOG_LEVEL', 'info'),
    'file': os.path.join(os.path.dirname(__file__), '..', 'logs', 'cron.log')
}
