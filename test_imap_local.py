#!/usr/bin/env python3
"""
GAC - Test de Conexión IMAP usando servidor local del hosting
"""

import sys
import os
import json
import imaplib
import socket
from dotenv import load_dotenv

# Cargar .env
script_path = os.path.abspath(__file__)
project_root = os.path.dirname(script_path)
env_path = os.path.join(project_root, '.env')
if os.path.exists(env_path):
    load_dotenv(env_path)

cron_dir = os.path.join(project_root, 'cron')
sys.path.insert(0, project_root)
os.chdir(cron_dir)

from cron.database import Database, USE_PYMYSQL
from cron.repositories import EmailAccountRepository

def test_imap_local():
    """Probar conexión IMAP usando servidor local del hosting"""
    print("=" * 60)
    print("GAC - Test de Conexión IMAP (Servidor Local)")
    print("=" * 60 + "\n")
    
    try:
        accounts = EmailAccountRepository.find_by_type('imap')
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        # Probar con la primera cuenta
        account = accounts[0]
        account_email = account['email']
        provider_config = account.get('provider_config', '{}')
        
        print(f"Probando cuenta: {account_email}")
        print("-" * 60)
        
        config = json.loads(provider_config) if provider_config else {}
        username = config.get('imap_user') or account_email
        password = config.get('imap_password') or ''
        
        if not password:
            print("✗ Contraseña no configurada")
            return False
        
        # Usar servidor local del hosting (como el sistema anterior)
        local_server = 'premium211.web-hosting.com'
        port = 993
        encryption = 'ssl'
        
        print(f"Servidor local: {local_server}")
        print(f"Puerto: {port}")
        print(f"Usuario: {username}\n")
        
        print("Intentando conectar al servidor local...", end=" ")
        
        old_timeout = socket.getdefaulttimeout()
        socket.setdefaulttimeout(10)
        
        try:
            mail = imaplib.IMAP4_SSL(local_server, port)
            mail.login(username, password)
            mail.select('INBOX')
            status, messages = mail.search(None, 'ALL')
            
            if status == 'OK':
                email_ids = messages[0].split()
                print(f"✓ CONECTADO")
                print(f"✓ Total de emails en INBOX: {len(email_ids)}")
                
                # Leer los últimos 3 emails
                if email_ids:
                    print(f"\nÚltimos 3 emails:")
                    for email_id in reversed(email_ids[-3:]):
                        status, msg_data = mail.fetch(email_id, '(RFC822)')
                        if status == 'OK':
                            import email
                            from email.header import decode_header
                            email_body = msg_data[0][1]
                            msg = email.message_from_bytes(email_body)
                            subject = decode_header(msg['Subject'] or '')[0][0]
                            if isinstance(subject, bytes):
                                subject = subject.decode('utf-8', errors='ignore')
                            print(f"  - {subject[:50]}")
                
                mail.close()
                mail.logout()
                
                print("\n" + "=" * 60)
                print("✓ ¡ÉXITO! El servidor local funciona")
                print("=" * 60)
                print("\nRecomendación: Actualiza el provider_config de las")
                print("cuentas para usar 'premium211.web-hosting.com' en lugar")
                print("de 'imap.gmail.com'")
                print("=" * 60)
                
                return True
            else:
                print("✗ Error al buscar emails")
                return False
                
        except socket.timeout:
            print("✗ Timeout")
            return False
        except socket.error as e:
            print(f"✗ Error de red: {e}")
            return False
        except imaplib.IMAP4.error as e:
            print(f"✗ Error IMAP: {e}")
            return False
        except Exception as e:
            print(f"✗ Error: {e}")
            return False
        finally:
            socket.setdefaulttimeout(old_timeout)
        
    except Exception as e:
        print(f"✗ Error fatal: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        Database.close_connections()

if __name__ == '__main__':
    sys.exit(0 if test_imap_local() else 1)
