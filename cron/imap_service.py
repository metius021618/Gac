"""
GAC - Servicio IMAP para Python
"""

import imaplib
import email
from email.header import decode_header
import logging
import json
from datetime import datetime

logger = logging.getLogger(__name__)


class ImapService:
    """Servicio para leer emails desde IMAP"""
    
    def __init__(self):
        pass
    
    def read_account(self, account):
        """Leer emails de una cuenta IMAP"""
        # Parsear configuración
        config = self._parse_provider_config(account.get('provider_config', '{}'))
        
        server = config.get('imap_server', '')
        port = config.get('imap_port', 993)
        encryption = config.get('imap_encryption', 'ssl')
        username = config.get('imap_user', '')
        password = config.get('imap_password', '')
        
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
            
            mail.login(username, password)
            mail.select('INBOX')
            
            # Buscar emails (últimos 50)
            status, messages = mail.search(None, 'ALL')
            
            if status != 'OK':
                raise Exception("Error al buscar emails")
            
            email_ids = messages[0].split()
            
            # Leer desde el más reciente (últimos 50)
            emails = []
            start = max(0, len(email_ids) - 50)
            
            for email_id in reversed(email_ids[start:]):
                try:
                    email_data = self._read_email(mail, email_id)
                    if email_data:
                        emails.append(email_data)
                except Exception as e:
                    logger.error(f"Error al leer email #{email_id}: {e}")
                    continue
            
            mail.close()
            mail.logout()
            
            return emails
            
        except Exception as e:
            logger.error(f"Error al conectar con IMAP: {e}")
            raise
    
    def _read_email(self, mail, email_id):
        """Leer un email específico"""
        status, msg_data = mail.fetch(email_id, '(RFC822)')
        
        if status != 'OK':
            return None
        
        email_body = msg_data[0][1]
        msg = email.message_from_bytes(email_body)
        
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
        
        # Obtener fecha
        date_str = msg['Date'] or ''
        try:
            date_tuple = email.utils.parsedate_tz(date_str)
            if date_tuple:
                timestamp = email.utils.mktime_tz(date_tuple)
                date = datetime.fromtimestamp(timestamp).strftime('%Y-%m-%d %H:%M:%S')
            else:
                date = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                timestamp = datetime.now().timestamp()
        except:
            date = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            timestamp = datetime.now().timestamp()
        
        # Obtener cuerpo
        body_text, body_html = self._get_email_body(msg)
        
        return {
            'message_number': int(email_id),
            'subject': subject,
            'from': from_email,
            'from_name': self._extract_name(from_addr),
            'to': recipients,  # Lista de destinatarios
            'to_primary': recipients[0] if recipients else '',  # Primer destinatario (principal)
            'date': date,
            'timestamp': int(timestamp),
            'body': body_text or body_html or '',
            'body_text': body_text,
            'body_html': body_html
        }
    
    def _get_email_body(self, msg):
        """Extraer cuerpo del email"""
        body_text = ''
        body_html = ''
        
        if msg.is_multipart():
            for part in msg.walk():
                content_type = part.get_content_type()
                content_disposition = str(part.get("Content-Disposition"))
                
                if "attachment" not in content_disposition:
                    try:
                        payload = part.get_payload(decode=True)
                        if payload:
                            charset = part.get_content_charset() or 'utf-8'
                            content = payload.decode(charset, errors='ignore')
                            
                            if content_type == "text/plain":
                                body_text = content
                            elif content_type == "text/html":
                                body_html = content
                    except Exception as e:
                        logger.error(f"Error al decodificar parte del email: {e}")
        else:
            try:
                payload = msg.get_payload(decode=True)
                if payload:
                    charset = msg.get_content_charset() or 'utf-8'
                    content = payload.decode(charset, errors='ignore')
                    content_type = msg.get_content_type()
                    
                    if content_type == "text/plain":
                        body_text = content
                    elif content_type == "text/html":
                        body_html = content
            except Exception as e:
                logger.error(f"Error al decodificar email: {e}")
        
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
    
    def _extract_email(self, address_string):
        """Extraer email de string de dirección"""
        import re
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