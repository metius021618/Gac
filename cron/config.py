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
    'warehouse_sync_interval': int(os.getenv('CRON_WAREHOUSE_SYNC_INTERVAL', 60))
}

# Configuración de Logging
LOG_CONFIG = {
    'level': os.getenv('LOG_LEVEL', 'info'),
    'file': os.path.join(os.path.dirname(__file__), '..', 'logs', 'cron.log')
}
