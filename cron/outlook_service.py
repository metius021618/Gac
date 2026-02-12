"""
GAC - Servicio Microsoft Graph API para Outlook Personal / Hotmail
OAuth estable y compatible con Microsoft Graph v1.0
"""

import base64
import json
import logging
import re
import time
import requests
from datetime import datetime
from email.utils import parsedate_tz, mktime_tz

from cron.config import OUTLOOK_CONFIG
from cron.repositories import EmailAccountRepository

logger = logging.getLogger(__name__)

# Timeouts: Graph API a veces tarda; 60s + 1 reintento evita "Read timed out" ocasional
OUTLOOK_TOKEN_TIMEOUT = 45
OUTLOOK_GRAPH_TIMEOUT = 60


def _decode_jwt_payload(token):
    try:
        parts = token.split('.')
        if len(parts) != 3:
            return None
        payload_b64 = parts[1] + '=' * (-len(parts[1]) % 4)
        return json.loads(base64.urlsafe_b64decode(payload_b64))
    except Exception:
        return None


def _parse_date(date_str):
    if not date_str:
        return datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S')
    try:
        if 'T' in date_str:
            dt = datetime.fromisoformat(date_str.replace('Z', '+00:00'))
            return dt.strftime('%Y-%m-%d %H:%M:%S')
        t = parsedate_tz(date_str)
        if t:
            return datetime.utcfromtimestamp(mktime_tz(t)).strftime('%Y-%m-%d %H:%M:%S')
    except Exception:
        pass
    return datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S')


class OutlookService:

    def __init__(self):
        self.client_id = OUTLOOK_CONFIG.get('client_id', '').strip()
        self.client_secret = OUTLOOK_CONFIG.get('client_secret', '').strip()
        self.redirect_uri = OUTLOOK_CONFIG.get('redirect_uri', '').strip()
        self.tenant_id = (OUTLOOK_CONFIG.get('tenant_id') or '').strip() or 'consumers'

    def _get_tenant(self):
        return 'consumers' if self.tenant_id.lower() in ['common', 'organizations'] else self.tenant_id

    def _refresh_tokens(self, refresh_token):
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
            'redirect_uri': self.redirect_uri
        }

        response = requests.post(token_url, data=data, timeout=OUTLOOK_TOKEN_TIMEOUT)

        if response.status_code != 200:
            raise Exception(f"Token refresh rechazado: {response.text[:300]}")

        token_data = response.json()
        access = token_data.get('access_token')
        new_refresh = token_data.get('refresh_token')

        if not access:
            raise Exception("Microsoft no devolvió access_token")

        payload = _decode_jwt_payload(access)
        if payload:
            aud = payload.get('aud', '')
            scp = payload.get('scp', '')

            logger.info("JWT aud=%s scp=%s", aud, scp)

            if 'graph.microsoft.com' not in aud.lower():
                raise Exception("Token inválido (aud incorrecto)")

            if 'Mail.Read' not in scp:
                raise Exception("Token sin Mail.Read — reautorizar")

        return access, new_refresh

    def read_account(self, account, max_messages=50):

        refresh_token = (account.get('oauth_refresh_token') or '').strip()
        email = (account.get('email') or '').strip().lower()
        account_id = account.get('id')

        if not refresh_token or not email:
            raise Exception("Cuenta Outlook sin refresh_token")

        access_token, new_refresh = self._refresh_tokens(refresh_token)

        if new_refresh and account_id:
            EmailAccountRepository.update_oauth_tokens(account_id, access_token, new_refresh)

        headers = {
            'Authorization': f'Bearer {access_token}',
            'Accept': 'application/json'
        }

        # Outlook personal → usar /me
        inbox_url = 'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages'

        params = {
            '$top': min(max_messages, 100),
            '$select': 'id,subject,from,toRecipients,receivedDateTime,body,bodyPreview'
        }

        def _fetch_inbox():
            return requests.get(inbox_url, headers=headers, params=params, timeout=OUTLOOK_GRAPH_TIMEOUT)

        try:
            response = _fetch_inbox()
        except (requests.exceptions.Timeout, requests.exceptions.ReadTimeout) as e:
            logger.warning("Outlook Graph timeout, reintentando en 3s: %s", e)
            time.sleep(3)
            response = _fetch_inbox()

        if response.status_code == 401:
            logger.warning("401 Graph → retry refresh")

            access_token, new_refresh = self._refresh_tokens(refresh_token)

            if new_refresh and account_id:
                EmailAccountRepository.update_oauth_tokens(account_id, access_token, new_refresh)

            headers['Authorization'] = f'Bearer {access_token}'
            try:
                response = requests.get(inbox_url, headers=headers, params=params, timeout=OUTLOOK_GRAPH_TIMEOUT)
            except (requests.exceptions.Timeout, requests.exceptions.ReadTimeout) as e:
                logger.warning("Outlook Graph timeout (tras refresh), reintentando: %s", e)
                time.sleep(3)
                response = requests.get(inbox_url, headers=headers, params=params, timeout=OUTLOOK_GRAPH_TIMEOUT)

        if response.status_code == 401:
            raise Exception("Microsoft Graph rechazó Mail.Read — reautorizar")

        response.raise_for_status()
        messages = response.json().get('value', [])

        emails = []

        for msg in messages:
            try:
                body = msg.get('body', {})
                body_html = body.get('content', '') if body.get('contentType') == 'html' else ''
                body_text = body.get('content', '') if body.get('contentType') == 'text' else ''
                if not body_html and not body_text:
                    body_text = msg.get('bodyPreview', '')

                from_obj = msg.get('from', {}).get('emailAddress', {})
                from_email = from_obj.get('address', '').lower()
                from_name = from_obj.get('name', '')

                to_list = [
                    r.get('emailAddress', {}).get('address', '').lower()
                    for r in msg.get('toRecipients', [])
                    if r.get('emailAddress', {}).get('address')
                ]

                emails.append({
                    'message_number': msg.get('id'),
                    'subject': msg.get('subject', ''),
                    'from': from_email,
                    'from_name': from_name,
                    'to': to_list or [email],
                    'to_primary': email,
                    'date': _parse_date(msg.get('receivedDateTime')),
                    'timestamp': int(datetime.utcnow().timestamp()),
                    'body': body_html or body_text or '',
                    'body_text': body_text,
                    'body_html': body_html
                })

            except Exception as e:
                logger.debug("Error parse msg: %s", str(e))

        emails.sort(key=lambda x: x.get('date', ''), reverse=True)
        return emails
