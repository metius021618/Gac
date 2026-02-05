"""
GAC - Servicio Microsoft Graph API para Python
Lee correos de cuentas Outlook/Hotmail usando OAuth (refresh_token guardado en email_accounts).
El destinatario (to_primary) es siempre la cuenta Outlook que estamos leyendo.
"""

import logging
import re
import requests
from datetime import datetime
from email.utils import parsedate_tz, mktime_tz

from cron.config import OUTLOOK_CONFIG

logger = logging.getLogger(__name__)


def _extract_email(addr_str):
    """Extraer dirección de email de 'Name <email@domain.com>'."""
    if not addr_str or not isinstance(addr_str, str):
        return ''
    m = re.search(r'[\w.+-]+@[\w.-]+\.\w+', addr_str)
    return m.group(0).lower() if m else addr_str.strip().lower()


def _extract_name(addr_str):
    """Extraer nombre de 'Name <email@domain.com>'."""
    if not addr_str or not isinstance(addr_str, str):
        return ''
    m = re.match(r'^\s*([^<]+)\s*<', addr_str)
    return (m.group(1).strip().strip('"') or '') if m else ''


def _parse_date(date_str):
    """Parsear fecha ISO 8601 o RFC2822 a Y-m-d H:%M:%S."""
    if not date_str:
        return datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    try:
        # Intentar ISO 8601 (formato de Microsoft Graph)
        if 'T' in date_str:
            dt = datetime.fromisoformat(date_str.replace('Z', '+00:00'))
            return dt.strftime('%Y-%m-%d %H:%M:%S')
        # Fallback a RFC2822
        t = parsedate_tz(date_str)
        if t:
            ts = mktime_tz(t)
            return datetime.fromtimestamp(ts).strftime('%Y-%m-%d %H:%M:%S')
    except Exception:
        pass
    return datetime.now().strftime('%Y-%m-%d %H:%M:%S')


class OutlookService:
    """Servicio para leer emails desde Microsoft Graph API (OAuth refresh_token)."""

    def __init__(self):
        self.client_id = OUTLOOK_CONFIG.get('client_id', '')
        self.client_secret = OUTLOOK_CONFIG.get('client_secret', '')
        self.tenant_id = (OUTLOOK_CONFIG.get('tenant_id') or '').strip() or 'common'

    def _get_access_token(self, refresh_token):
        """Obtener access_token usando refresh_token. Scope debe coincidir con el de la autorización (PHP)."""
        token_url = f'https://login.microsoftonline.com/{self.tenant_id}/oauth2/v2.0/token'
        # Mismo scope que OutlookController.php (User.Read + Mail.Read + offline_access)
        scope = 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Read offline_access'
        data = {
            'client_id': self.client_id,
            'client_secret': self.client_secret,
            'refresh_token': refresh_token,
            'grant_type': 'refresh_token',
            'scope': scope
        }
        try:
            response = requests.post(token_url, data=data, timeout=30)
            response.raise_for_status()
            token_data = response.json()
            return token_data.get('access_token')
        except Exception as e:
            logger.error(f"Error al obtener access_token: {e}")
            raise Exception(f"Error al renovar token: {str(e)}")

    def read_account(self, account, max_messages=50):
        """
        Leer emails de una cuenta Outlook/Hotmail.
        account: dict con id, email, oauth_refresh_token (y opcional oauth_token).
        El destinatario (to_primary) es siempre account['email'] (el buzón que leemos).
        Devuelve lista de dicts con el mismo formato que ImapService para reutilizar filtro y guardado.
        """
        refresh_token = (account.get('oauth_refresh_token') or '').strip()
        account_email = (account.get('email') or '').strip().lower()
        if not refresh_token or not account_email:
            raise Exception("Cuenta Outlook sin refresh_token o email")

        if not self.client_id or not self.client_secret:
            raise Exception("OUTLOOK_CLIENT_ID y OUTLOOK_CLIENT_SECRET deben estar en .env")

        # Obtener access_token
        access_token = self._get_access_token(refresh_token)
        if not access_token:
            raise Exception("No se pudo obtener access_token")

        # Listar mensajes (últimos max_messages)
        graph_url = f'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages'
        headers = {
            'Authorization': f'Bearer {access_token}',
            'Content-Type': 'application/json'
        }
        params = {
            '$top': min(max_messages, 100),
            '$orderby': 'receivedDateTime desc',
            '$select': 'id,subject,from,toRecipients,receivedDateTime,body,bodyPreview'
        }

        try:
            response = requests.get(graph_url, headers=headers, params=params, timeout=30)
            response.raise_for_status()
            result = response.json()
        except Exception as e:
            logger.error(f"Microsoft Graph list messages error: {e}")
            raise

        messages = result.get('value', [])
        if not messages:
            return []

        emails = []
        for msg in messages:
            try:
                # Obtener mensaje completo si no tenemos body completo
                msg_id = msg.get('id')
                if not msg_id:
                    continue
                
                # Si el mensaje ya tiene body completo, usarlo; si no, hacer otra llamada
                body_content = msg.get('body', {})
                if not body_content.get('content'):
                    # Obtener mensaje completo
                    msg_url = f'https://graph.microsoft.com/v1.0/me/messages/{msg_id}'
                    msg_response = requests.get(msg_url, headers=headers, timeout=30)
                    if msg_response.status_code == 200:
                        msg = msg_response.json()
                        body_content = msg.get('body', {})

                email_data = self._parse_message(msg, account_email)
                if email_data:
                    emails.append(email_data)
            except Exception as e:
                logger.debug(f"Error al leer mensaje {msg.get('id', 'unknown')}: {e}")
                continue

        # Ordenar por fecha (más reciente primero)
        emails.sort(key=lambda e: e.get('date', ''), reverse=True)
        return emails

    def _parse_message(self, msg, account_email):
        """Convertir mensaje Microsoft Graph al formato usado por ImapService (subject, from, to_primary, date, body_*)."""
        subject = msg.get('subject', '')
        from_obj = msg.get('from', {})
        from_email = from_obj.get('emailAddress', {}).get('address', '').lower()
        from_name = from_obj.get('emailAddress', {}).get('name', '')
        
        to_recipients = msg.get('toRecipients', [])
        to_list = [r.get('emailAddress', {}).get('address', '').lower() for r in to_recipients if r.get('emailAddress', {}).get('address')]
        to_primary = account_email  # Siempre la cuenta que estamos leyendo

        # Body: puede venir en body.content (HTML) o bodyPreview (texto)
        body_content = msg.get('body', {})
        body_html = body_content.get('content', '') if body_content.get('contentType') == 'html' else ''
        body_text = body_content.get('content', '') if body_content.get('contentType') == 'text' else ''
        if not body_text and not body_html:
            body_text = msg.get('bodyPreview', '') or ''
        body = body_html or body_text or ''

        # Fecha: receivedDateTime (ISO 8601)
        received_date = msg.get('receivedDateTime', '')
        date = _parse_date(received_date)

        return {
            'message_number': msg.get('id', ''),
            'subject': subject,
            'from': from_email,
            'from_name': from_name,
            'to': to_list or [account_email],
            'to_primary': to_primary,
            'date': date,
            'timestamp': int(datetime.now().timestamp()),
            'body': body,
            'body_text': body_text,
            'body_html': body_html
        }
