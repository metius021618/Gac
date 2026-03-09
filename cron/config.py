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
    'scopes': [os.getenv('GMAIL_SCOPES', 'https://www.googleapis.com/auth/gmail.readonly')],
    # Topic Pub/Sub para Gmail Watch (event-driven). Formato: projects/PROJECT_ID/topics/TOPIC_NAME
    'pubsub_topic': os.getenv('GMAIL_PUBSUB_TOPIC', ''),
    # URL pública del webhook (configurar esta misma URL en la suscripción Push de Pub/Sub)
    'webhook_url': os.getenv('GMAIL_WEBHOOK_URL', 'https://app.pocoyoni.com/gmail/push'),
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
    # Intervalo en segundos de espera entre ciclo y ciclo (sync_loop.py). Tras ejecutar, espera esto y vuelve a iniciar. Mínimo 0.5.
    'reader_loop_seconds': max(0.5, float(os.getenv('CRON_READER_LOOP_SECONDS', '0.5').replace(',', '.'))),
    # Lectura Gmail: solo últimos N mensajes y solo de los últimos N días (optimiza tiempo por ciclo).
    'gmail_max_messages': min(100, max(10, int(os.getenv('CRON_GMAIL_MAX_MESSAGES', 20)))),
    'gmail_newer_than_days': max(1, min(30, int(os.getenv('CRON_GMAIL_NEWER_THAN_DAYS', 1)))),
    # Mínimo segundos entre ejecuciones del lector Gmail (evita 429: límite 15.000 unidades/usuario/minuto).
    # IMAP y Outlook siguen cada ciclo; solo Gmail se espacia. Por defecto 60 s.
    'gmail_min_interval_seconds': max(30, int(os.getenv('CRON_GMAIL_MIN_INTERVAL_SECONDS', '60'))),
    # Si true, NO ejecutar el lector Gmail en el sync_loop (solo eventos vía webhook + process_gmail_history).
    'gmail_event_driven': os.getenv('CRON_GMAIL_EVENT_DRIVEN', 'false').lower() in ('true', '1', 'yes'),
    # Monitor: alertar si no llegan eventos Gmail en esta cantidad de horas (check_gmail_watch_health.py).
    'gmail_no_event_alert_hours': max(1, int(os.getenv('CRON_GMAIL_NO_EVENT_ALERT_HOURS', '24'))),
}

# Configuración de Logging
LOG_CONFIG = {
    'level': os.getenv('LOG_LEVEL', 'info'),
    'file': os.path.join(os.path.dirname(__file__), '..', 'logs', 'cron.log')
}
