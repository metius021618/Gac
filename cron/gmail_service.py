"""
GAC - Servicio Gmail API para Python
Lee correos de cuentas Gmail usando OAuth (refresh_token guardado en email_accounts).
Soporta correo matriz: cuando muchos correos se reenvían a una sola cuenta, el destinatario
original se extrae de los headers (To, X-Original-To) para guardar recipient_email correcto.
"""

import base64
import logging
import re
from datetime import datetime
from email.header import decode_header as email_decode_header
from email.utils import parsedate_tz, mktime_tz

from cron.config import GMAIL_CONFIG

logger = logging.getLogger(__name__)

# Imports opcionales de Google (solo si están instalados)
try:
    from google.oauth2.credentials import Credentials
    from google.auth.transport.requests import Request
    from googleapiclient.discovery import build
    try:
        from google_auth_httplib2 import AuthorizedHttp
        import httplib2
        _HAS_HTTPLIB2 = True
    except ImportError:
        _HAS_HTTPLIB2 = False
    GMAIL_AVAILABLE = True
except ImportError:
    GMAIL_AVAILABLE = False
    _HAS_HTTPLIB2 = False


def _fix_alt_param_uri(uri):
    """Corregir parámetro alt en la URI: solo 'json' es válido (origen del error: librería/proxy puede enviar jso, jsonn)."""
    if not uri:
        return uri
    # Solo tocar URLs de Google API que llevan alt
    if 'alt=' not in uri and 'alt%3D' not in uri:
        return uri
    # Normalizar: cualquier valor de alt por alt=json (evita 400 Invalid value "jso"/"jsonn" for query parameter 'alt')
    new_uri = re.sub(r'alt=([^&]*)', 'alt=json', uri)
    new_uri = re.sub(r'alt%3D([^&]*)', 'alt%3Djson', new_uri)
    if new_uri != uri:
        logger.debug("Gmail API: corregido parámetro alt en la petición")
    return new_uri


_requests_alt_fix_applied = False


def _install_alt_param_fix_for_requests():
    """Parche para transporte por defecto (requests): corregir alt en la URL antes de enviar.
    Se usa cuando no está disponible google-auth-httplib2; así el fix aplica siempre."""
    global _requests_alt_fix_applied
    if _requests_alt_fix_applied:
        return
    try:
        import requests
        _orig = requests.Session.request

        def _patched_request(self, method, url, **kwargs):
            url = _fix_alt_param_uri(url or '')
            return _orig(self, method, url, **kwargs)

        requests.Session.request = _patched_request
        _requests_alt_fix_applied = True
        logger.debug("Gmail API: parche aplicado a requests.Session para corregir alt")
    except Exception as e:
        logger.warning("Gmail API: no se pudo aplicar parche a requests: %s", e)


def _fix_alt_param_http(base_http):
    """Envuelve el Http (httplib2) para normalizar alt=... a alt=json."""
    class FixAltHttp(object):
        def __init__(self, http):
            self._http = http

        def request(self, uri, method='GET', body=None, headers=None, redirections=5, connection_type=None):
            uri = _fix_alt_param_uri(uri or '')
            return self._http.request(uri, method=method, body=body, headers=headers,
                                      redirections=redirections, connection_type=connection_type)

        def __getattr__(self, name):
            return getattr(self._http, name)
    return FixAltHttp(base_http)


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

        # Corregir parámetro alt (jso/jsonn -> json): origen del error en algunos entornos al construir la URI.
        if _HAS_HTTPLIB2:
            base_http = httplib2.Http()
            fixed_http = _fix_alt_param_http(base_http)
            auth_http = AuthorizedHttp(creds, http=fixed_http)
            service = build('gmail', 'v1', http=auth_http, cache_discovery=False)
        else:
            _install_alt_param_fix_for_requests()
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

    def setup_watch(self, account):
        """
        Registrar Gmail Watch (users.watch) para la cuenta. Gmail notificará cambios vía Pub/Sub.
        account: dict con oauth_refresh_token, email.
        Returns: dict con historyId (str) y expiration (str, ms desde epoch) o None si falla.
        Requiere GMAIL_PUBSUB_TOPIC en config (projects/PROJECT_ID/topics/TOPIC_NAME).
        """
        topic = (GMAIL_CONFIG.get('pubsub_topic') or '').strip()
        if not topic or not topic.startswith('projects/'):
            logger.warning("Gmail Watch: GMAIL_PUBSUB_TOPIC no configurado o inválido. Configure .env con el topic completo.")
            return None
        refresh_token = (account.get('oauth_refresh_token') or '').strip()
        if not refresh_token:
            logger.warning("Gmail Watch: cuenta sin oauth_refresh_token")
            return None
        if not self.client_id or not self.client_secret:
            logger.warning("Gmail Watch: GMAIL_CLIENT_ID / GMAIL_CLIENT_SECRET no configurados")
            return None
        creds = Credentials(
            token=None,
            refresh_token=refresh_token,
            token_uri='https://oauth2.googleapis.com/token',
            client_id=self.client_id,
            client_secret=self.client_secret,
            scopes=['https://www.googleapis.com/auth/gmail.readonly']
        )
        creds.refresh(Request())
        if _HAS_HTTPLIB2:
            base_http = httplib2.Http()
            fixed_http = _fix_alt_param_http(base_http)
            auth_http = AuthorizedHttp(creds, http=fixed_http)
            service = build('gmail', 'v1', http=auth_http, cache_discovery=False)
        else:
            _install_alt_param_fix_for_requests()
            service = build('gmail', 'v1', credentials=creds, cache_discovery=False)
        try:
            body = {'topicName': topic, 'labelIds': ['INBOX']}
            resp = service.users().watch(userId='me', body=body).execute()
            history_id = resp.get('historyId')
            expiration = resp.get('expiration')  # string, ms
            if history_id is not None:
                logger.info("Gmail Watch registrado: historyId=%s expiration=%s", history_id, expiration)
                return {'historyId': str(history_id), 'expiration': str(expiration) if expiration else ''}
            return None
        except Exception as e:
            logger.error("Gmail Watch error: %s", e)
            return None

    def _build_service(self, account):
        """Construir cliente Gmail API autenticado para la cuenta (para history.list y messages.get)."""
        refresh_token = (account.get('oauth_refresh_token') or '').strip()
        if not refresh_token or not self.client_id or not self.client_secret:
            return None
        creds = Credentials(
            token=None,
            refresh_token=refresh_token,
            token_uri='https://oauth2.googleapis.com/token',
            client_id=self.client_id,
            client_secret=self.client_secret,
            scopes=['https://www.googleapis.com/auth/gmail.readonly']
        )
        creds.refresh(Request())
        if _HAS_HTTPLIB2:
            base_http = httplib2.Http()
            fixed_http = _fix_alt_param_http(base_http)
            auth_http = AuthorizedHttp(creds, http=fixed_http)
            return build('gmail', 'v1', http=auth_http, cache_discovery=False)
        _install_alt_param_fix_for_requests()
        return build('gmail', 'v1', credentials=creds, cache_discovery=False)

    def fetch_history_message_ids(self, account, start_history_id):
        """
        history.list desde start_history_id; devuelve (list of message ids, new_history_id).
        new_history_id es el del último response (para guardar en BD).
        """
        service = self._build_service(account)
        if not service:
            return [], None
        try:
            start = int(start_history_id) if start_history_id else None
            if start is None:
                return [], None
            msg_ids = []
            page_token = None
            last_history_id = None
            while True:
                params = {'userId': 'me', 'startHistoryId': start}
                if page_token:
                    params['pageToken'] = page_token
                resp = service.users().history().list(**params).execute()
                last_history_id = resp.get('historyId')
                for h in resp.get('history', []):
                    for m in h.get('messages', []):
                        mid = m.get('id')
                        if mid and mid not in msg_ids:
                            msg_ids.append(mid)
                page_token = resp.get('nextPageToken')
                if not page_token:
                    break
            return msg_ids, last_history_id
        except Exception as e:
            logger.error("fetch_history_message_ids error: %s", e)
            return [], None

    def get_message_metadata(self, service, msg_id, account_email):
        """Obtener solo metadata (subject, from, to, date) para filtrar por asunto sin descargar cuerpo."""
        if not service or not msg_id:
            return None
        try:
            msg = service.users().messages().get(
                userId='me', id=msg_id, format='metadata',
                metadataHeaders=['Subject', 'From', 'To', 'Date', 'X-Original-To', 'Original-Recipient']
            ).execute()
            payload = msg.get('payload') or {}
            headers = payload.get('headers') or []
            subject = _decode_header_value(_get_header(headers, 'Subject'))
            from_raw = _get_header(headers, 'From')
            to_raw = _get_header(headers, 'To')
            x_original_to = _get_header(headers, 'X-Original-To')
            original_recipient = _get_header(headers, 'Original-Recipient')
            date_header_str = _get_header(headers, 'Date')
            from_email = _extract_email(from_raw)
            to_list = [e.strip().lower() for e in re.findall(r'[\w.+-]+@[\w.-]+\.\w+', to_raw or '') if e]
            orig = x_original_to or original_recipient
            to_primary = (_extract_email(orig) or (to_list[0] if to_list else account_email) or account_email).strip().lower()
            internal_date = msg.get('internalDate')
            date = _parse_internal_date(internal_date) if internal_date else _parse_date(date_header_str)
            return {
                'message_number': msg_id,
                'subject': subject,
                'from': from_email,
                'to_primary': to_primary,
                'date': date,
                'to': to_list or [account_email],
            }
        except Exception as e:
            logger.debug("get_message_metadata %s: %s", msg_id, e)
            return None

    def get_message_full(self, service, msg_id, account_email):
        """Descargar mensaje completo y parsear (para cuando el asunto ya hizo match)."""
        if not service or not msg_id:
            return None
        try:
            msg = service.users().messages().get(
                userId='me', id=msg_id, format='full'
            ).execute()
            return self._parse_message(msg, account_email)
        except Exception as e:
            logger.debug("get_message_full %s: %s", msg_id, e)
            return None

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
