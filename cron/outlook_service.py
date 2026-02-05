"""
GAC - Servicio Microsoft Graph API para Python
Lee correos Outlook/Hotmail usando OAuth (refresh_token guardado en email_accounts).
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
    """Decodificar payload del JWT (sin validar firma)."""
    try:
        parts = token.split('.')
        if len(parts) != 3:
            return None
        payload_b64 = parts[1] + '=' * (-len(parts[1]) % 4)
        payload = base64.urlsafe_b64decode(payload_b64)
        return json.loads(payload)
    except Exception:
        return None


def _extract_email(addr_str):
    if not addr_str or not isinstance(addr_str, str):
        return ''
    m = re.search(r'[\w.+-]+@[\w.-]+\.\w+', addr_str)
    return m.group(0).lower() if m else addr_str.strip().lower()


def _extract_name(addr_str):
    if not addr_str or not isinstance(addr_str, str):
        return ''
    m = re.match(r'^\s*([^<]+)\s*<', addr_str)
    return (m.group(1).strip().strip('"') or '') if m else ''


def _parse_date(date_str):
    if not date_str:
        return datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S')
    try:
        if 'T' in date_str:
            dt = datetime.fromisoformat(date_str.replace('Z', '+00:00'))
            return dt.strftime('%Y-%m-%d %H:%M:%S')
        t = parsedate_tz(date_str)
        if t:
            ts = mktime_tz(t)
            return datetime.utcfromtimestamp(ts).strftime('%Y-%m-%d %H:%M:%S')
    except Exception:
        pass
    return datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S')


class OutlookService:
    """Servicio Microsoft Graph API"""

    def __init__(self):
        self.client_id = OUTLOOK_CONFIG.get('client_id', '').strip()
        self.client_secret = OUTLOOK_CONFIG.get('client_secret', '').strip()
        self.redirect_uri = OUTLOOK_CONFIG.get('redirect_uri', '').strip()
        self.tenant_id = (OUTLOOK_CONFIG.get('tenant_id') or '').strip() or 'consumers'  # Outlook personal fix

    def _get_tenant(self):
        """Evita usar common para Outlook personal"""
        if self.tenant_id.lower() in ['common', 'organizations']:
            return 'consumers'
        return self.tenant_id

    def _refresh_tokens(self, refresh_token):
        """Renovar access_token desde refresh_token"""
        tenant = self._get_tenant()
        token_url = f'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token'

        scope = (
            'openid profile email offline_access '
            'https://graph.microsoft.com/User.Read '
            'https://graph.microsoft.com/Mail.Read'
        )

        data = {
            'client_id': self.client_id,
            'client_secret': self.client_secret,
            'refresh_token': refresh_token,
            'grant_type': 'refresh_token',
            'scope': scope,
            'redirect_uri': self.redirect_uri  # obligatorio Outlook personal
        }

        try:
            response = requests.post(token_url, data=data, timeout=30)

            if response.status_code != 200:
                logger.error("Token endpoint error %s: %s", response.status_code, response.text[:400])

            response.raise_for_status()
            token_data = response.json()

            access = token_data.get('access_token')
            new_refresh = token_data.get('refresh_token') or None
            scope_returned = token_data.get('scope', '')

            logger.info("Scope en token: %s", scope_returned or "(vacío)")

            # JWT debug
            if access:
                payload = _decode_jwt_payload(access)
                if payload:
                    aud = payload.get('aud', '')
                    scp = payload.get('scp', '') or payload.get('roles', '')
                    logger.info("JWT aud=%s scp=%s", aud, scp)

                    if 'graph.microsoft.com' not in str(aud).lower():
                        raise Exception(f"Token inválido (aud={aud})")

                    if 'Mail.Read' not in scp:
                        raise Exception("Token NO incluye Mail.Read real")

            return access, new_refresh

        except Exception as e:
            logger.error("Error refresh token: %s", str(e))
            raise Exception(f"Refresh token inválido: {str(e)}")

    def read_account(self, account, max_messages=50):
        refresh_token = (account.get('oauth_refresh_token') or '').strip()
        account_email = (account.get('email') or '').strip().lower()

        if not refresh_token or not account_email:
            raise Exception("Cuenta Outlook sin refresh_token o email")

        if not self.client_id or not self.client_secret or not self.redirect_uri:
            raise Exception("OUTLOOK_CLIENT_ID / SECRET / REDIRECT_URI deben existir")

        account_id = account.get('id')

        # Renovar token
        access_token, new_refresh = self._refresh_tokens(refresh_token)

        if not access_token:
            raise Exception("No se pudo obtener access_token")

        logger.info("Token Outlook renovado correctamente")

        if new_refresh and account_id:
            EmailAccountRepository.update_oauth_tokens(account_id, access_token, new_refresh)
            refresh_token = new_refresh

        # Validar token en /me
        me_resp = requests.get(
            'https://graph.microsoft.com/v1.0/me',
            headers={'Authorization': f'Bearer {access_token}'},
            timeout=15
        )

        if me_resp.status_code == 401:
            raise Exception("Token rechazado en /me → reautorización obligatoria")

        me_data = me_resp.json() if me_resp.status_code == 200 else {}
        user_id = (me_data.get('id') or '').strip() or 'me'

        base = f'https://graph.microsoft.com/v1.0/users/{user_id}'
        inbox_url = f'{base}/mailFolders/inbox/messages'

        headers = {
            'Authorization': f'Bearer {access_token}',
            'Accept': 'application/json'
        }

        params = {'$top': min(max_messages, 100)}

        # Primera llamada inbox
        response = requests.get(inbox_url, headers=headers, params=params, timeout=30)

        # Retry si 401
        if response.status_code == 401:
            logger.warning("401 Graph → renovando token y reintentando")

            access_token, new_refresh = self._refresh_tokens(refresh_token)

            if new_refresh and account_id:
                EmailAccountRepository.update_oauth_tokens(account_id, access_token, new_refresh)

            headers['Authorization'] = f'Bearer {access_token}'
            response = requests.get(inbox_url, headers=headers, params=params, timeout=30)

        # Error final
        if response.status_code == 401:
            logger.error("401 definitivo Graph — token inválido Mail.Read")
            raise Exception("Microsoft Graph 401 — Reautorizar Outlook")

        response.raise_for_status()
        result = response.json()

        messages = result.get('value', [])
        if not messages:
            return []

        emails = []

        for msg in messages:
            try:
                msg_id = msg.get('id')
                if not msg_id:
                    continue

                # Obtener body completo si falta
                body_content = msg.get('body', {})
                if not body_content.get('content'):
                    msg_url = f'{base}/messages/{msg_id}'
                    msg_response = requests.get(msg_url, headers=headers, timeout=20)
                    if msg_response.status_code == 200:
                        msg = msg_response.json()
                        body_content = msg.get('body', {})

                email_data = self._parse_message(msg, account_email)
                if email_data:
                    emails.append(email_data)

            except Exception as e:
                logger.debug("Error leyendo mensaje %s: %s", msg.get('id'), str(e))

        emails.sort(key=lambda e: e.get('date', ''), reverse=True)
        return emails

    def _parse_message(self, msg, account_email):
        subject = msg.get('subject', '')

        from_obj = msg.get('from', {})
        from_email = from_obj.get('emailAddress', {}).get('address', '').lower()
        from_name = from_obj.get('emailAddress', {}).get('name', '')

        to_recipients = msg.get('toRecipients', [])
        to_list = [
            r.get('emailAddress', {}).get('address', '').lower()
            for r in to_recipients if r.get('emailAddress', {}).get('address')
        ]

        body_content = msg.get('body', {})
        body_html = body_content.get('content', '') if body_content.get('contentType') == 'html' else ''
        body_text = body_content.get('content', '') if body_content.get('contentType') == 'text' else ''

        if not body_text and not body_html:
            body_text = msg.get('bodyPreview', '') or ''

        received_date = msg.get('receivedDateTime', '')
        date = _parse_date(received_date)

        return {
            'message_number': msg.get('id', ''),
            'subject': subject,
            'from': from_email,
            'from_name': from_name,
            'to': to_list or [account_email],
            'to_primary': account_email,
            'date': date,
            'timestamp': int(datetime.utcnow().timestamp()),
            'body': body_html or body_text or '',
            'body_text': body_text,
            'body_html': body_html
        }
