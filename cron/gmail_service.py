"""
GAC - Servicio Gmail API para Python
Lee correos de cuentas Gmail usando OAuth (refresh_token guardado en email_accounts).
Soporta correo matriz: cuando muchos correos se reenvían a una sola cuenta, el destinatario
original se extrae de los headers (To, X-Original-To) para guardar recipient_email correcto.
"""

import base64
import logging
import os
import re
from datetime import datetime, timezone
from email.header import decode_header as email_decode_header
from email.utils import parsedate_tz, mktime_tz

from cron.config import GMAIL_CONFIG, LOG_CONFIG

logger = logging.getLogger(__name__)

# Archivo donde guardamos "no llamar Gmail API hasta esta hora" cuando Google devuelve 429
GMAIL_RATE_LIMIT_FILE = os.path.join(os.path.dirname(LOG_CONFIG['file']), 'gmail_rate_limit_until.txt')

# Regex para extraer "Retry after 2026-02-12T22:58:40.059Z" del error 429
RETRY_AFTER_RE = re.compile(r'[Rr]etry after\s+([\dT:\-.]+Z)', re.I)


def _parse_retry_after_from_error(error_msg):
    """Si el mensaje contiene 'Retry after <ISO timestamp>', devuelve ese datetime (UTC). Si no, None."""
    m = RETRY_AFTER_RE.search(error_msg)
    if not m:
        return None
    try:
        s = m.group(1).strip()
        # Aceptar con o sin milisegundos
        for fmt in ('%Y-%m-%dT%H:%M:%S.%fZ', '%Y-%m-%dT%H:%M:%SZ'):
            try:
                return datetime.strptime(s[:26] if '.' in s else s, fmt).replace(tzinfo=timezone.utc)
            except ValueError:
                continue
        return None
    except Exception:
        return None


def _save_gmail_rate_limit_until(retry_after_utc):
    """Guarda en disco la hora hasta la que no debemos llamar a Gmail API."""
    try:
        with open(GMAIL_RATE_LIMIT_FILE, 'w') as f:
            f.write(retry_after_utc.strftime('%Y-%m-%dT%H:%M:%SZ'))
    except Exception as e:
        logger.warning("No se pudo guardar gmail rate limit until: %s", e)


def is_gmail_rate_limited():
    """True si debemos omitir la llamada a Gmail API (cooldown por 429)."""
    if not os.path.isfile(GMAIL_RATE_LIMIT_FILE):
        return False, None
    try:
        with open(GMAIL_RATE_LIMIT_FILE) as f:
            line = (f.read() or '').strip()
        if not line:
            return False, None
        until = datetime.strptime(line[:19], '%Y-%m-%dT%H:%M:%S').replace(tzinfo=timezone.utc)
        if datetime.now(timezone.utc) < until:
            return True, until
        return False, None
    except Exception:
        return False, None

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


def _decode_header_value(value):
    """Decodificar header MIME (=?UTF-8?B?...?=) a string para comparar asuntos correctamente."""
    if not value or not isinstance(value, str):
        return value or ''
    if '=?' not in value:
        return value.strip()
    try:
        parts = email_decode_header(value)
        result = []
        for part, charset in parts:
            if isinstance(part, bytes):
                result.append(part.decode(charset or 'utf-8', errors='replace'))
            else:
                result.append(part or '')
        return ''.join(result).strip()
    except Exception:
        return value.strip()


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

    def read_account(self, account, max_messages=50):
        """
        Leer emails de una cuenta Gmail.
        account: dict con id, email, oauth_refresh_token (y opcional oauth_token).
        Si la cuenta es correo matriz (recibe reenvíos), el destinatario original se obtiene
        de los headers To / X-Original-To; si no, se usa account['email'].
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

        # Filtro por fecha: solo correos recientes (menos llamadas y más rápido)
        # Excluir categoría Promociones para no leer códigos de correos promocionales (mostraría ese en vez del de verificación)
        try:
            from cron.config import CRON_CONFIG
            newer_days = CRON_CONFIG.get('gmail_newer_than_days', 1)
            query = 'newer_than:{}d'.format(newer_days) if newer_days else None
        except Exception:
            query = 'newer_than:1d'
        if query:
            query = query + ' -category:promotions'
        else:
            query = '-category:promotions'
        list_params = {
            'userId': 'me',
            'labelIds': ['INBOX'],
            'maxResults': min(max_messages, 100),
        }
        list_params['q'] = query

        # Listar IDs de mensajes (INBOX, filtrado por fecha, últimos max_messages)
        try:
            result = service.users().messages().list(**list_params).execute()
        except Exception as e:
            err_str = str(e)
            if '429' in err_str or 'rateLimitExceeded' in err_str or 'rate limit' in err_str.lower():
                retry_after = _parse_retry_after_from_error(err_str)
                if retry_after:
                    _save_gmail_rate_limit_until(retry_after)
                    logger.warning("Gmail 429: no se llamará a Gmail API hasta %s UTC", retry_after.strftime('%Y-%m-%d %H:%M:%S'))
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
        Destinatario (to_primary): en correo matriz con reenvíos se usa el destinatario original
        desde To o X-Original-To; si no hay, se usa account_email. internalDate = fecha real de recepción."""
        payload = msg.get('payload') or {}
        headers = payload.get('headers') or []

        subject = _decode_header_value(_get_header(headers, 'Subject'))
        from_raw = _get_header(headers, 'From')
        to_raw = _get_header(headers, 'To')
        x_original_to = _get_header(headers, 'X-Original-To')
        original_recipient = _get_header(headers, 'Original-Recipient')  # Algunos reenvíos (RFC 2298)
        date_header_str = _get_header(headers, 'Date')

        from_email = _extract_email(from_raw)
        from_name = _extract_name(from_raw)
        to_list = [e.strip().lower() for e in re.findall(r'[\w.+-]+@[\w.-]+\.\w+', to_raw or '') if e]
        # Correo matriz: destinatario original desde X-Original-To, Original-Recipient (reenvíos) o To; si no, buzón
        orig = x_original_to or original_recipient
        if orig:
            to_primary = _extract_email(orig) or (to_list[0] if to_list else account_email)
        else:
            to_primary = (to_list[0] if to_list else account_email)
        to_primary = (to_primary or account_email).strip().lower()

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
