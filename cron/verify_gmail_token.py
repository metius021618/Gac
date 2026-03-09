#!/usr/bin/env python3
"""
GAC - Verificar que el token de la cuenta Gmail matriz es válido.
Usa la misma lógica que el cron: lee la cuenta desde gmail_matrix + email_accounts
y prueba a refrescar el token y construir el cliente Gmail.

Uso (en el servidor, desde la raíz del proyecto):
  python3 cron/verify_gmail_token.py

Salida:
  - "Token OK" + email de la cuenta → el cron podrá usar esa cuenta.
  - Mensaje de error → reautorizar desde Admin → Configuración → Configurar/Cambiar cuenta Gmail matriz.
"""

import os
import sys

script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)
os.chdir(parent_dir)

from cron.config import LOG_CONFIG
from cron.database import Database
from cron.repositories import EmailAccountRepository
from cron.gmail_service import GmailService
import logging

logging.basicConfig(
    level=getattr(logging, LOG_CONFIG.get('level', 'info').upper(), logging.INFO),
    format='%(message)s',
    handlers=[logging.StreamHandler()]
)
logger = logging.getLogger(__name__)


def main():
    gaccount = EmailAccountRepository.get_gmail_matrix_account()
    if not gaccount:
        logger.error("No hay cuenta Gmail matriz configurada (tabla gmail_matrix).")
        return 1

    email = (gaccount.get('email') or '').strip() or '(sin email)'
    has_refresh = bool((gaccount.get('oauth_refresh_token') or '').strip())
    if not has_refresh:
        logger.error("La cuenta %s no tiene oauth_refresh_token guardado. Reautoriza desde Configuración.", email)
        return 1

    gmail = GmailService()
    service = gmail._build_service(gaccount)
    if not service:
        logger.error("El token está expirado o revocado. Reautoriza desde Admin → Configuración → Gmail matriz.")
        return 1

    # Probar una llamada mínima a la API para confirmar
    try:
        profile = service.users().getProfile(userId='me').execute()
        profile_email = (profile.get('emailAddress') or '').strip().lower()
        logger.info("Token OK. Cuenta: %s", profile_email or email)
        return 0
    except Exception as e:
        logger.error("Token aceptado pero falló getProfile: %s", e)
        return 1


if __name__ == '__main__':
    try:
        sys.exit(main())
    finally:
        Database.close_connections()
