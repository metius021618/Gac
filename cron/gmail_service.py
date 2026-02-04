"""
GAC - Servicio Gmail API para Python
Lee correos de cuentas Gmail usando OAuth (refresh_token guardado en email_accounts).
El destinatario (to_primary) es siempre la cuenta Gmail que estamos leyendo.
"""

import base64
import logging
import re
from datetime import datetime
from email.utils import parsedate_tz, mktime_tz

from cron.config import GMAIL_CONFIG

logger = logging.getLogger(__name__)

# Imports opcionales de Google (solo si están instalados)
try:
    from google.oauth2.credentials import Credentials
    from google.auth.transport.requests import Request
    from googleapiclient.discovery import build
    GMAIL_AVAILABLE = True
except ImportError:
    GMAIL_AVAILABLE = False


def _get_header(headers, name):
    """Obtener valor de un header (case-insensitive)."""
    name = name.lower()
    for h in (headers or []):
        if (h.get('name') or '').lower() == name:
            return h.get('value') or ''
    return ''


def _decode_body(data):
    """Decodificar body base64url de Gmail API."""
    if not data:
        return ''
    try:
        return base64.urlsafe_b64decode(data).decode('utf-8', errors='replace')
    except Exception:
        return ''


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
    """Parsear fecha RFC2822 a Y-m-d H:%M:%S."""
    if not date_str:
        return datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    try:
        t = parsedate_tz(date_str)
        if t:
            ts = mktime_tz(t)
            return datetime.fromtimestamp(ts).strftime('%Y-%m-%d %H:%M:%S')
    except Exception:
        pass
    return datetime.now().strftime('%Y-%m-%d %H:%M:%S')


def _parse_internal_date(internal_date_ms):
    """Convertir internalDate de Gmail (ms desde epoch) a Y-m-d H:%M:%S. Fecha real de recepción."""
    if internal_date_ms is None:
        return None
    try:
        ts = int(internal_date_ms) / 1000.0
        return datetime.fromtimestamp(ts).strftime('%Y-%m-%d %H:%M:%S')
    except (TypeError, ValueError, OSError):
        return None


def _get_body_from_payload(payload):
    """Extraer body HTML/texto del payload de un mensaje Gmail."""
    body_html = ''
    body_text = ''
    if not payload:
        return body_text, body_html

    def walk_parts(part):
        nonlocal body_html, body_text
        mime = (part.get('mimeType') or '').lower()
        body = part.get('body')
        if body and body.get('data'):
            content = _decode_body(body.get('data'))
            if not content:
                return
            if 'html' in mime:
                body_html = content
            elif 'plain' in mime or 'text' in mime:
                body_text = content
        for p in part.get('parts') or []:
            walk_parts(p)

    # Cuerpo directo en payload.body
    if payload.get('body') and payload['body'].get('data'):
        content = _decode_body(payload['body'].get('data'))
        if content:
            mime = (payload.get('mimeType') or '').lower()
            if 'html' in mime:
                body_html = content
            else:
                body_text = content
    walk_parts(payload)
    return body_text, body_html


class GmailService:
    """Servicio para leer emails desde Gmail API (OAuth refresh_token)."""

    def __init__(self):
        if not GMAIL_AVAILABLE:
            raise RuntimeError("Google API client no instalado. Ejecuta: pip install google-api-python-client google-auth-httplib2 google-auth-oauthlib")
        self.client_id = GMAIL_CONFIG.get('client_id', '')
        self.client_secret = GMAIL_CONFIG.get('client_secret', '')

    def read_account(self, account, max_messages=200):
        """
        Leer emails de una cuenta Gmail.
        account: dict con id, email, oauth_refresh_token (y opcional oauth_token).
        El destinatario (to_primary) es siempre account['email'] (el buzón que leemos).
        Devuelve lista de dicts con el mismo formato que ImapService para reutilizar filtro y guardado.
        """
        refresh_token = (account.get('oauth_refresh_token') or '').strip()
        account_email = (account.get('email') or '').strip().lower()
        if not refresh_token or not account_email:
            raise Exception("Cuenta Gmail sin refresh_token o email")

        if not self.client_id or not self.client_secret:
            raise Exception("GMAIL_CLIENT_ID y GMAIL_CLIENT_SECRET deben estar en .env")

        creds = Credentials(
            token=None,
            refresh_token=refresh_token,
            token_uri='https://oauth2.googleapis.com/token',
            client_id=self.client_id,
            client_secret=self.client_secret,
            scopes=['https://www.googleapis.com/auth/gmail.readonly']
        )
        creds.refresh(Request())

        service = build('gmail', 'v1', credentials=creds, cache_discovery=False)

        # Listar IDs de mensajes (INBOX, últimos max_messages)
        try:
            result = service.users().messages().list(
                userId='me',
                labelIds=['INBOX'],
                maxResults=min(max_messages, 500)
            ).execute()
        except Exception as e:
            logger.error(f"Gmail list messages error: {e}")
            raise

        messages = result.get('messages', [])
        if not messages:
            return []

        emails = []
        for msg_ref in messages:
            msg_id = msg_ref.get('id')
            if not msg_id:
                continue
            try:
                msg = service.users().messages().get(
                    userId='me',
                    id=msg_id,
                    format='full'
                ).execute()
                email_data = self._parse_message(msg, account_email)
                if email_data:
                    emails.append(email_data)
            except Exception as e:
                logger.debug(f"Error al leer mensaje {msg_id}: {e}")
                continue

        # Ordenar por fecha real (más reciente primero) para que la consulta devuelva el último recibido
        emails.sort(key=lambda e: e.get('date', ''), reverse=True)
        return emails

    def _parse_message(self, msg, account_email):
        """Convertir mensaje Gmail API al formato usado por ImapService (subject, from, to_primary, date, body_*).
        Usamos internalDate (fecha real de recepción en Gmail) para que Consulta tu código muestre el correo más reciente."""
        payload = msg.get('payload') or {}
        headers = payload.get('headers') or []

        subject = _get_header(headers, 'Subject')
        from_raw = _get_header(headers, 'From')
        to_raw = _get_header(headers, 'To')
        date_header_str = _get_header(headers, 'Date')

        from_email = _extract_email(from_raw)
        from_name = _extract_name(from_raw)
        to_list = [e.strip().lower() for e in re.findall(r'[\w.+-]+@[\w.-]+\.\w+', to_raw or '') if e]
        to_primary = account_email

        body_text, body_html = _get_body_from_payload(payload)
        body = body_text or body_html or ''
        # Preferir internalDate (cuando Gmail recibió el correo) sobre el header Date (puede venir mal del remitente)
        internal_date = msg.get('internalDate')
        date = _parse_internal_date(internal_date) if internal_date else _parse_date(date_header_str)

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
