"""
GAC - Servicio IMAP para Python
"""

import imaplib
import re
import email
from email.header import decode_header
from email.policy import default as email_policy_default
import logging
import json
from datetime import datetime, timedelta
from cron.repositories import SettingsRepository

logger = logging.getLogger(__name__)

# Perú = UTC-5. Guardar fecha en hora Perú (igual que Gmail) para que se muestre como en el correo.
PERU_OFFSET = timedelta(hours=5)


def _timestamp_to_peru_str(ts):
    """Convertir timestamp UTC a hora Perú (America/Lima) y devolver Y-m-d H:%M:%S."""
    utc_dt = datetime.utcfromtimestamp(ts)
    peru_dt = utc_dt - PERU_OFFSET
    return peru_dt.strftime('%Y-%m-%d %H:%M:%S')


class ImapService:
    """Servicio para leer emails desde IMAP"""
    
    def __init__(self):
        pass

    @staticmethod
    def _imap_login(mail, username: str, password: str) -> None:
        """
        imaplib usa ASCII por defecto en LOGIN; ñ/acentos rompen o fuerzan PLAIN.
        Orden: LOGIN con UTF-8 en el cable si hace falta (Dovecot/SiteGround suelen aceptarlo);
        si falla, AUTHENTICATE PLAIN; si solo ASCII, LOGIN normal.
        """
        needs_utf8 = any(ord(c) > 127 for c in (username + password))
        enc_orig = mail._encoding

        def plain_login() -> None:
            blob = b'\x00' + username.encode('utf-8') + b'\x00' + password.encode('utf-8')

            def plain_cb(response):
                if not response:
                    return blob
                return b''

            mail.authenticate('PLAIN', plain_cb)

        try:
            if needs_utf8:
                mail._encoding = 'utf-8'
                try:
                    mail.login(username, password)
                except imaplib.IMAP4.error:
                    mail._encoding = enc_orig
                    logger.info('IMAP: LOGIN UTF-8 no aceptado o credenciales; probando AUTHENTICATE PLAIN')
                    plain_login()
            else:
                mail.login(username, password)
        except UnicodeEncodeError:
            mail._encoding = enc_orig
            logger.info('IMAP: LOGIN con ASCII imposible; probando AUTHENTICATE PLAIN')
            plain_login()
    
    def read_account(self, account):
        """Leer emails de una cuenta IMAP"""
        # Parsear configuración
        config = self._parse_provider_config(account.get('provider_config', '{}'))
        
        server = config.get('imap_server', '')
        port = config.get('imap_port', 993)
        encryption = config.get('imap_encryption', 'ssl')
        username = config.get('imap_user', '') or ''
        password = config.get('imap_password', '') or ''
        if isinstance(username, bytes):
            username = username.decode('utf-8', errors='replace')
        if isinstance(password, bytes):
            password = password.decode('utf-8', errors='replace')
        username = username.strip()
        password = password.strip()
        
        if not server or not username or not password:
            raise Exception("Configuración IMAP incompleta")
        
        # Conectar a IMAP
        try:
            if encryption == 'ssl':
                mail = imaplib.IMAP4_SSL(server, port)
            else:
                mail = imaplib.IMAP4(server, port)
                if encryption == 'tls':
                    mail.starttls()
            
            self._imap_login(mail, username, password)
            mail.select('INBOX')

            # Estrategia incremental por UID: evita releer siempre el mismo bloque histórico.
            account_id = account.get('id')
            setting_key = f"imap_last_uid_{account_id}" if account_id is not None else "imap_last_uid_default"
            last_uid_raw = SettingsRepository.get(setting_key, '0')
            try:
                last_uid = int(str(last_uid_raw or '0').strip())
            except Exception:
                last_uid = 0

            max_fetch = 80
            email_uids = []
            if last_uid > 0:
                # Solo correos nuevos desde el último UID procesado.
                status, messages = mail.uid('search', None, f'UID {last_uid + 1}:*')
                if status == 'OK' and messages and messages[0]:
                    email_uids = messages[0].split()
                else:
                    logger.debug("IMAP incremental: sin nuevos UIDs (last_uid=%s)", last_uid)
                    return []
            else:
                # Primera corrida: traer recientes para sembrar estado sin recorrer todo el histórico.
                try:
                    since_date = (datetime.now() - timedelta(days=3)).strftime('%d-%b-%Y')
                    status, messages = mail.search(None, 'SINCE', since_date)
                    if status == 'OK' and messages and messages[0]:
                        seq_ids = messages[0].split()
                        recent_seq = seq_ids[-max_fetch:] if len(seq_ids) > max_fetch else seq_ids
                        for seq_id in recent_seq:
                            ustatus, udata = mail.fetch(seq_id, '(UID)')
                            if ustatus == 'OK' and udata and udata[0]:
                                raw = udata[0][0] if isinstance(udata[0], tuple) else udata[0]
                                match = re.search(rb'UID\s+(\d+)', raw if isinstance(raw, bytes) else str(raw).encode('utf-8', errors='ignore'))
                                if match:
                                    email_uids.append(match.group(1))
                except Exception:
                    email_uids = []
                if not email_uids:
                    status, messages = mail.uid('search', None, 'ALL')
                    if status != 'OK':
                        raise Exception("Error al buscar emails por UID")
                    email_uids = messages[0].split() if messages and messages[0] else []
                if len(email_uids) > max_fetch:
                    email_uids = email_uids[-max_fetch:]

            # Leer desde el más reciente (máximo max_fetch para ir más rápido)
            emails = []
            start = max(0, len(email_uids) - max_fetch)

            account_email = (account.get('email') or '').strip().lower()
            max_uid_seen = last_uid
            for email_uid in reversed(email_uids[start:]):
                try:
                    email_data = self._read_email(mail, email_uid, account_email, use_uid=True)
                    if email_data:
                        emails.append(email_data)
                        try:
                            current_uid = int(email_uid)
                            if current_uid > max_uid_seen:
                                max_uid_seen = current_uid
                        except Exception:
                            pass
                except Exception as e:
                    logger.error(f"Error al leer email UID #{email_uid}: {e}")
                    continue

            # Guardar el mayor UID visto para siguiente corrida incremental.
            if max_uid_seen > last_uid:
                SettingsRepository.set(setting_key, str(max_uid_seen), 'string')
            
            mail.close()
            mail.logout()
            
            return emails
            
        except Exception as e:
            logger.error(f"Error al conectar con IMAP: {e}")
            raise
    
    def _read_email(self, mail, email_id, account_email='', use_uid=False):
        """Leer un email específico sin marcar como leído (BODY.PEEK[] no setea \\Seen)."""
        # BODY.PEEK[] evita que el servidor marque el mensaje como leído; RFC822 suele setear \Seen
        if use_uid:
            status, msg_data = mail.uid('fetch', email_id, '(BODY.PEEK[] UID)')
        else:
            status, msg_data = mail.fetch(email_id, '(BODY.PEEK[])')
        
        if status != 'OK':
            return None
        
        email_body = msg_data[0][1]
        msg = email.message_from_bytes(email_body, policy=email_policy_default)
        
        # Obtener asunto
        subject = self._decode_header(msg['Subject'] or '')
        
        # Obtener remitente
        from_addr = self._decode_header(msg['From'] or '')
        from_email = self._extract_email(from_addr)
        
        # Obtener destinatarios (To, Cc, Bcc)
        to_addrs = msg.get_all('To', [])
        cc_addrs = msg.get_all('Cc', [])
        bcc_addrs = msg.get_all('Bcc', [])
        
        recipients = []
        for addr_list in [to_addrs, cc_addrs, bcc_addrs]:
            if addr_list:
                for addr in addr_list:
                    decoded_addr = self._decode_header(addr)
                    email_addr = self._extract_email(decoded_addr)
                    if email_addr:
                        recipients.append(email_addr.lower())
        
        # Destinatario real: el servidor puede poner la cuenta maestra en Delivered-To
        # y el destinatario original en Envelope-to (ej. casa2025@pocoyoni.com).
        # Priorizar Envelope-To / X-Envelope-To sobre Delivered-To.
        delivery_recipient = ''
        for header_name in ('Envelope-To', 'X-Envelope-To', 'X-Original-To', 'Delivered-To'):
            raw = msg.get(header_name, '')
            if raw:
                decoded = self._decode_header(raw)
                addr = self._extract_email(decoded)
                if addr:
                    delivery_recipient = addr.lower()
                    break
        if delivery_recipient and delivery_recipient not in recipients:
            recipients.insert(0, delivery_recipient)
        
        # Destinatario principal: el que consultará el código
        to_primary = (delivery_recipient if delivery_recipient else (recipients[0] if recipients else ''))
        
        # Obtener cuerpo antes de posible fallback (necesitamos body para extraer Destinatario)
        body_text, body_html = self._get_email_body(msg)
        if not body_text and not body_html:
            body_text, body_html = self._get_body_modern(msg)
        if not body_text and not body_html:
            logger.warning(f"Email sin cuerpo extraído (asunto): {subject[:60]!r}")
        
        # Si el destinatario es la cuenta maestra (o vacío), intentar extraer del cuerpo: "Destinatario: casa2025@pocoyoni.com"
        if account_email and (not to_primary or to_primary.lower() == account_email.lower()):
            body_for_recipient = body_text or body_html or ''
            if body_for_recipient:
                extracted = self._extract_recipient_from_body(body_for_recipient, account_email)
                if extracted:
                    to_primary = extracted
                    logger.info(f"  Destinatario real desde cuerpo: {to_primary}")
                else:
                    logger.warning(f"  No se encontró destinatario en cuerpo (asunto: {subject[:50]}), se usará cuenta maestra")
        
        # Obtener fecha desde cabecera Date del correo; guardar en hora Perú (igual que Gmail / @pocoyoni)
        date_str = msg['Date'] or ''
        try:
            date_tuple = email.utils.parsedate_tz(date_str)
            if date_tuple:
                timestamp = email.utils.mktime_tz(date_tuple)
                date = _timestamp_to_peru_str(timestamp)
            else:
                date = _timestamp_to_peru_str(datetime.utcnow().timestamp())
                timestamp = datetime.utcnow().timestamp()
        except Exception:
            date = _timestamp_to_peru_str(datetime.utcnow().timestamp())
            timestamp = datetime.utcnow().timestamp()
        
        # Cuerpo ya obtenido arriba (para poder extraer destinatario del body)
        return {
            'message_number': int(email_id),
            'subject': subject,
            'from': from_email,
            'from_name': self._extract_name(from_addr),
            'to': recipients,
            'to_primary': to_primary,  # Destinatario real (para asociar el código al usuario que consulta)
            'date': date,
            'timestamp': int(timestamp),
            'body': body_text or body_html or '',
            'body_text': body_text,
            'body_html': body_html
        }
    
    def _get_body_modern(self, msg):
        """Usar get_body() (Python 3.6+) para extraer cuerpo; funciona mejor con multipart/alternative."""
        body_text = ''
        body_html = ''
        try:
            body_part = msg.get_body(preferencelist=('html', 'plain'))
            if body_part:
                content = body_part.get_content()
                if content:
                    ct = (body_part.get_content_type() or '').lower()
                    if 'html' in ct:
                        body_html = content
                    else:
                        body_text = content
        except AttributeError:
            pass
        except Exception as e:
            logger.debug(f"get_body falló: {e}")
        return body_text, body_html
    
    def _decode_payload(self, part):
        """Decodificar payload de una parte; intenta varios charsets."""
        payload = part.get_payload(decode=True)
        if payload is None:
            raw = part.get_payload(decode=False)
            if isinstance(raw, str):
                return raw
            if isinstance(raw, bytes):
                payload = raw
            else:
                return ''
        if not payload:
            return ''
        charset = part.get_content_charset() or 'utf-8'
        for enc in (charset, 'utf-8', 'latin-1', 'cp1252'):
            try:
                return payload.decode(enc, errors='strict')
            except (LookupError, UnicodeDecodeError):
                continue
        return payload.decode('utf-8', errors='ignore')
    
    def _get_email_body(self, msg):
        """Extraer cuerpo del email (robusto a distintos formatos MIME)."""
        body_text = ''
        body_html = ''
        
        if msg.is_multipart():
            for part in msg.walk():
                content_type = (part.get_content_type() or '').lower()
                content_disposition = str(part.get("Content-Disposition") or '')
                
                if "attachment" in content_disposition.lower():
                    continue
                # Ignorar contenedores multipart (sin contenido directo)
                if part.is_multipart():
                    continue
                try:
                    content = self._decode_payload(part)
                    if not content:
                        continue
                    # Aceptar text/plain, text/html y variantes (xhtml, etc.)
                    if 'text/plain' in content_type:
                        body_text = content
                    elif 'text/html' in content_type or 'html' in content_type:
                        body_html = content
                    elif content_type.startswith('text/'):
                        body_text = body_text or content
                except Exception as e:
                    logger.debug(f"Parte no decodificada: {e}")
        else:
            try:
                content = self._decode_payload(msg)
                content_type = (msg.get_content_type() or '').lower()
                if content:
                    if 'text/plain' in content_type:
                        body_text = content
                    elif 'text/html' in content_type or 'html' in content_type:
                        body_html = content
                    else:
                        body_text = content or body_text
            except Exception as e:
                logger.debug(f"Cuerpo no decodificado: {e}")
        
        return body_text, body_html
    
    def _decode_header(self, header):
        """Decodificar header MIME"""
        if not header:
            return ''
        
        decoded_parts = decode_header(header)
        decoded_string = ''
        
        for part, encoding in decoded_parts:
            if isinstance(part, bytes):
                if encoding:
                    decoded_string += part.decode(encoding, errors='ignore')
                else:
                    decoded_string += part.decode('utf-8', errors='ignore')
            else:
                decoded_string += part
        
        return decoded_string
    
    def _extract_recipient_from_body(self, body, master_email):
        """Extraer destinatario real del cuerpo: Destinatario/Para/mailto o cualquier @pocoyoni.com que no sea la cuenta maestra."""
        if not body or not master_email:
            return ''
        master_lower = master_email.lower()
        # 1) mailto: en enlaces (muchos correos ponen el destinatario ahí)
        for match in re.finditer(r'mailto:\s*([\w\.-]+@[\w\.-]+\.\w+)', body, re.IGNORECASE):
            addr = match.group(1).strip().lower()
            if addr != master_lower and 'pocoyoni.com' in addr:
                return addr
        # 2) Quitar tags HTML para buscar en texto
        body_clean = re.sub(r'<[^>]+>', ' ', body)
        body_clean = ' '.join(body_clean.split())
        # 3) Patrones con etiqueta: Destinatario: casa2025@..., Para: ..., etc.
        patterns = [
            r'Destinatario\s*[:\s]+\s*([\w\.-]+@[\w\.-]+\.\w+)',
            r'Para\s*[:\s]+\s*([\w\.-]+@[\w\.-]+\.\w+)',
            r'To\s*[:\s]+\s*([\w\.-]+@[\w\.-]+\.\w+)',
            r'Recipient\s*[:\s]+\s*([\w\.-]+@[\w\.-]+\.\w+)',
            r'Destinatario\s+[\w\s]*?([\w\.-]+@pocoyoni\.com)',
            r'Destinatario.*?([\w\.-]+@pocoyoni\.com)',
        ]
        for pat in patterns:
            match = re.search(pat, body_clean, re.IGNORECASE | re.DOTALL)
            if match:
                email_addr = match.group(1).strip().lower()
                if email_addr and email_addr != master_lower:
                    return email_addr
        # 4) Último recurso: cualquier @pocoyoni.com en el cuerpo que no sea la cuenta maestra
        all_pocoyoni = re.findall(r'([\w\.-]+@pocoyoni\.com)', body_clean, re.IGNORECASE)
        for addr in all_pocoyoni:
            addr = addr.strip().lower()
            if addr != master_lower:
                return addr
        return ''

    def _extract_email(self, address_string):
        """Extraer email de string de dirección"""
        match = re.search(r'[\w\.-]+@[\w\.-]+\.\w+', address_string)
        return match.group(0) if match else ''
    
    def _extract_name(self, address_string):
        """Extraer nombre de string de dirección"""
        import re
        match = re.search(r'^(.+?)\s*<', address_string)
        if match:
            name = match.group(1).strip().strip('"\'')
            return name
        return ''
    
    def _parse_provider_config(self, config_json):
        """Parsear configuración JSON"""
        try:
            return json.loads(config_json) if config_json else {}
        except:
            return {}