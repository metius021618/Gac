#!/usr/bin/env python3
"""
GAC - Test de lectura de emails desde cuenta maestra filtrando por destinatario
"""

import sys
import os
import imaplib
import email
from email.header import decode_header
import socket
from dotenv import load_dotenv

# Cargar .env
script_path = os.path.abspath(__file__)
project_root = os.path.dirname(script_path)
env_path = os.path.join(project_root, '.env')
if os.path.exists(env_path):
    load_dotenv(env_path)

def test_master_filter_by_recipient():
    """Probar lectura de emails desde cuenta maestra filtrando por destinatario"""
    print("=" * 60)
    print("GAC - Test IMAP (Cuenta Maestra - Filtrar por Destinatario)")
    print("=" * 60 + "\n")
    
    # Configuración de cuenta maestra
    local_server = 'premium211.web-hosting.com'
    port = 993
    master_user = 'streaming@pocoyoni.com'
    master_password = 'D3b+Vln0tj0Q'
    
    # Emails de prueba (destinatarios)
    test_recipients = [
        'loana1malu@pocoyoni.com',
        'piero2torres@pocoyoni.com',
        'kevincr62@pocoyoni.com',
        'cine003@pocoyoni.com'
    ]
    
    print(f"Conectando a cuenta maestra: {master_user}")
    print(f"Buscando correos para destinatarios: {', '.join(test_recipients)}\n")
    
    old_timeout = socket.getdefaulttimeout()
    socket.setdefaulttimeout(15)
    
    try:
        mail = imaplib.IMAP4_SSL(local_server, port)
        mail.login(master_user, master_password)
        mail.select('INBOX')
        
        # Buscar todos los emails
        status, messages = mail.search(None, 'ALL')
        
        if status != 'OK':
            print("✗ Error al buscar emails")
            return False
        
        email_ids = messages[0].split()
        print(f"✓ Total de emails en INBOX: {len(email_ids)}\n")
        
        # Leer los últimos 20 emails y filtrar por destinatario
        emails_by_recipient = {recipient: [] for recipient in test_recipients}
        other_emails = []
        
        print("Leyendo últimos 20 emails...\n")
        
        for email_id in reversed(email_ids[-20:]):
            try:
                status, msg_data = mail.fetch(email_id, '(RFC822)')
                if status != 'OK':
                    continue
                
                email_body = msg_data[0][1]
                msg = email.message_from_bytes(email_body)
                
                # Obtener destinatarios
                to_addrs = msg.get_all('To', [])
                cc_addrs = msg.get_all('Cc', [])
                bcc_addrs = msg.get_all('Bcc', [])
                
                all_recipients = []
                for addr_list in [to_addrs, cc_addrs, bcc_addrs]:
                    if addr_list:
                        for addr in addr_list:
                            # Extraer email de formato "Name <email@domain.com>" o solo "email@domain.com"
                            if '<' in addr:
                                email_addr = addr.split('<')[1].split('>')[0].strip()
                            else:
                                email_addr = addr.strip()
                            all_recipients.append(email_addr.lower())
                
                # Obtener asunto
                subject = decode_header(msg['Subject'] or '')[0][0]
                if isinstance(subject, bytes):
                    subject = subject.decode('utf-8', errors='ignore')
                
                # Obtener remitente
                from_addr = msg.get('From', '')
                if '<' in from_addr:
                    from_email = from_addr.split('<')[1].split('>')[0].strip()
                else:
                    from_email = from_addr.strip()
                
                # Clasificar por destinatario
                found = False
                for recipient in test_recipients:
                    if recipient.lower() in all_recipients:
                        emails_by_recipient[recipient].append({
                            'subject': subject,
                            'from': from_email,
                            'recipients': all_recipients
                        })
                        found = True
                        break
                
                if not found:
                    other_emails.append({
                        'subject': subject[:50],
                        'recipients': all_recipients[:3]  # Primeros 3
                    })
                
            except Exception as e:
                print(f"  Error al leer email #{email_id}: {e}")
                continue
        
        mail.close()
        mail.logout()
        
        # Mostrar resultados
        print("=" * 60)
        print("RESULTADOS:")
        print("=" * 60)
        
        for recipient in test_recipients:
            emails = emails_by_recipient[recipient]
            print(f"\n{recipient}:")
            if emails:
                print(f"  ✓ {len(emails)} email(s) encontrado(s)")
                for email_data in emails[:3]:  # Mostrar primeros 3
                    print(f"    - {email_data['subject'][:50]}")
                    print(f"      De: {email_data['from']}")
            else:
                print(f"  ✗ No se encontraron emails")
        
        print(f"\nOtros emails (no en lista de prueba): {len(other_emails)}")
        if other_emails:
            print("  Ejemplos:")
            for email_data in other_emails[:3]:
                print(f"    - {email_data['subject']}")
                print(f"      Para: {', '.join(email_data['recipients'][:2])}")
        
        print("\n" + "=" * 60)
        print("✓ ¡ÉXITO! El sistema puede leer y filtrar por destinatario")
        print("=" * 60)
        
        return True
        
    except Exception as e:
        print(f"✗ Error: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        socket.setdefaulttimeout(old_timeout)

if __name__ == '__main__':
    sys.exit(0 if test_master_filter_by_recipient() else 1)
