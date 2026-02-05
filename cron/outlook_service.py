"""
GAC - Servicio Microsoft Graph API para Python
Lee correos de cuentas Outlook/Hotmail usando OAuth (refresh_token guardado en email_accounts).
El destinatario (to_primary) es siempre la cuenta Outlook que estamos leyendo.
"""

import base64
import json
import logging
import re
import requests
from datetime import datetime
from email.utils import parsedate_tz, mktime_tz

from cron.config import OUTLOOK_CONFIG
from cron.repositories import EmailAccountRepository

logger = logging.getLogger(__name__)


def _decode_jwt_payload(token):
    """Decodificar payload del JWT (sin verificar firma) para ver aud, scp, etc."""
    try:
        parts = token.split('.')
        if len(parts) != 3:
            return None
        payload_b64 = parts[1]
        payload_b64 += '=' * (4 - len(payload_b64) % 4)
        payload = base64.urlsafe_b64decode(payload_b64)
        return json.loads(payload)
    except Exception:
        return None


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
        self.redirect_uri = (OUTLOOK_CONFIG.get('redirect_uri') or '').strip()

    def _refresh_tokens(self, refresh_token):
        """
        Renovar access_token (y opcionalmente refresh_token) usando refresh_token.
        Scope y redirect_uri deben coincidir con el de la autorización (PHP).
        Returns: (access_token, new_refresh_token) - new_refresh_token puede ser None si Microsoft no lo devuelve.
        """
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
        # redirect_uri obligatorio para cuentas personales; debe ser el mismo que al conectar
        if self.redirect_uri:
            data['redirect_uri'] = self.redirect_uri
        try:
            response = requests.post(token_url, data=data, timeout=30)
            if response.status_code != 200:
                logger.error(
                    "Token endpoint respondió %s: %s",
                    response.status_code,
                    response.text[:500] if response.text else "(vacío)"
                )
            response.raise_for_status()
            token_data = response.json()
            access = token_data.get('access_token')
            new_refresh = token_data.get('refresh_token') or None
            # Log scope que Microsoft devuelve (para ver si incluye Mail.Read)
            scope_returned = token_data.get('scope', '')
            logger.info("Scope en token: %s", scope_returned or "(vacío)")
            if scope_returned and 'Mail.Read' not in scope_returned:
                logger.warning("El token NO incluye Mail.Read. Añade OUTLOOK_REDIRECT_URI en .env y reconecta Outlook.")
            # Inspeccionar JWT: aud (audience) debe ser Graph; scp = permisos reales
            if access:
                payload = _decode_jwt_payload(access)
                if payload:
                    aud = payload.get('aud', '')
                    scp = payload.get('scp', '') or payload.get('roles', '')
                    logger.info("JWT aud=%s scp=%s", aud, scp)
                    if aud and 'graph.microsoft.com' not in str(aud).lower():
                        logger.warning("El token no es para Graph (aud=%s). Revisa tenant y app.", aud)
            return (access, new_refresh)
        except Exception as e:
            logger.error(f"Error al renovar token: {e}")
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

        account_id = account.get('id')
        # Renovar access_token (y opcionalmente refresh_token)
        access_token, new_refresh = self._refresh_tokens(refresh_token)
        if not access_token:
            raise Exception("No se pudo obtener access_token")
        logger.info("Token Outlook renovado correctamente")
        if new_refresh and account_id:
            EmailAccountRepository.update_oauth_tokens(account_id, access_token, new_refresh)
            refresh_token = new_refresh

        # Diagnóstico: si /me funciona pero /me/mailFolders falla con 401, el token no tiene Mail.Read
        me_resp = requests.get(
            'https://graph.microsoft.com/v1.0/me',
            headers={'Authorization': f'Bearer {access_token}', 'Content-Type': 'application/json'},
            timeout=10
        )
        if me_resp.status_code == 200 and me_resp.json():
            pass  # Token válido para User.Read
        elif me_resp.status_code == 401:
            logger.error("Token rechazado incluso en /me (User.Read). Revisa OUTLOOK_CLIENT_ID/SECRET en .env.")
            raise Exception("Token no válido para Graph API")

        # Listar mensajes: petición mínima ($top solo) para evitar 401 con cuentas personales
        graph_url = 'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages'
        headers = {
            'Authorization': f'Bearer {access_token}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
        params = {'$top': min(max_messages, 100)}

        response = requests.get(graph_url, headers=headers, params=params, timeout=30)
        if response.status_code == 401:
            logger.warning("401 en Graph: renegociando token y reintentando una vez...")
            try:
                access_token, new_refresh = self._refresh_tokens(refresh_token)
                if access_token and account_id:
                    EmailAccountRepository.update_oauth_tokens(
                        account_id, access_token, new_refresh if new_refresh else refresh_token
                    )
                if access_token:
                    headers = {'Authorization': f'Bearer {access_token}', 'Content-Type': 'application/json'}
                    response = requests.get(graph_url, headers=headers, params=params, timeout=30)
            except Exception as e:
                logger.error(f"Reintento tras 401 falló: {e}")

        try:
            response.raise_for_status()
            result = response.json()
        except Exception as e:
            if response.status_code == 401:
                logger.error("Graph 401 headers: %s", dict(response.headers))
                try:
                    err_body = response.json()
                    code = err_body.get('error', {}).get('code', '') if isinstance(err_body.get('error'), dict) else ''
                    msg = err_body.get('error', {}).get('message', '') if isinstance(err_body.get('error'), dict) else ''
                    logger.error("Graph 401: code=%s message=%s", code, msg or response.text[:300])
                except Exception:
                    logger.error("Graph 401 body: %s", response.text[:500] if response.text else "(vacío)")
                logger.error(
                    "401 en bandeja de entrada. El token no tiene permiso Mail.Read. "
                    "Reconecta Outlook desde la web (Registro de Accesos → Conectar Outlook) y vuelve a ejecutar."
                )
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
