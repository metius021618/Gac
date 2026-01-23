#!/usr/bin/env python3
"""
GAC - Test de conexión IMAP usando passwords de BD antigua como usuarios
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

# Mapeo manual de emails a passwords (de la BD antigua)
# Estos "passwords" probablemente son los usuarios IMAP
EMAIL_TO_PASSWORD = {
    'cine003@pocoyoni.com': 'ENRIQUEBR',
    'cine004@pocoyoni.com': 'ENRIQUEBR',
    'cine005@pocoyoni.com': 'ENRIQUEBR',
    'cine006@pocoyoni.com': 'ENRIQUEBR',
    'cine007@pocoyoni.com': 'ENRIQUEBR',
    # Agregar más según sea necesario
}

def test_with_old_passwords():
    """Probar usando passwords de BD antigua como usuarios IMAP"""
    print("=" * 60)
    print("GAC - Test IMAP (Password de BD antigua como Usuario)")
    print("=" * 60 + "\n")
    
    try:
        accounts = EmailAccountRepository.find_by_type('imap')[:5]
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        local_server = 'premium211.web-hosting.com'
        port = 993
        master_password = 'D3b+Vln0tj0Q'  # Contraseña de la cuenta maestra
        
        old_timeout = socket.getdefaulttimeout()
        socket.setdefaulttimeout(15)
        
        success_count = 0
        working_method = None
        
        for account in accounts:
            account_email = account['email']
            old_password_user = EMAIL_TO_PASSWORD.get(account_email, '')
            
            print(f"Probando: {account_email}")
            print("-" * 60)
            
            if not old_password_user:
                print("  ⚠ No se encontró password en mapeo (agregar a EMAIL_TO_PASSWORD)")
                print()
                continue
            
            print(f"  Password de BD antigua: {old_password_user}")
            
            # Probar diferentes combinaciones
            combinations = [
                {
                    'name': 'Password como usuario + Contraseña maestra',
                    'user': old_password_user,
                    'pass': master_password
                },
                {
                    'name': 'Password como usuario + Password como contraseña',
                    'user': old_password_user,
                    'pass': old_password_user
                },
                {
                    'name': 'Email como usuario + Contraseña maestra',
                    'user': account_email,
                    'pass': master_password
                },
                {
                    'name': 'Password@dominio como usuario + Contraseña maestra',
                    'user': f"{old_password_user}@pocoyoni.com",
                    'pass': master_password
                },
            ]
            
            found = False
            
            for combo in combinations:
                print(f"  Probando: {combo['name']}...", end=" ")
                
                try:
                    mail = imaplib.IMAP4_SSL(local_server, port)
                    mail.login(combo['user'], combo['pass'])
                    mail.select('INBOX')
                    status, messages = mail.search(None, 'ALL')
                    
                    if status == 'OK':
                        email_ids = messages[0].split()
                        print(f"✓ CONECTADO ({len(email_ids)} emails)")
                        
                        # Leer último email
                        if email_ids:
                            status, msg_data = mail.fetch(email_ids[-1], '(RFC822)')
                            if status == 'OK':
                                import email
                                from email.header import decode_header
                                email_body = msg_data[0][1]
                                msg = email.message_from_bytes(email_body)
                                subject = decode_header(msg['Subject'] or '')[0][0]
                                if isinstance(subject, bytes):
                                    subject = subject.decode('utf-8', errors='ignore')
                                print(f"    Último email: {subject[:40]}")
                        
                        mail.close()
                        mail.logout()
                        
                        if not working_method:
                            working_method = combo
                        
                        success_count += 1
                        found = True
                        break
                    
                except imaplib.IMAP4.error as e:
                    error_msg = str(e)
                    if 'AUTHENTICATIONFAILED' in error_msg:
                        print("✗ Auth failed")
                    else:
                        print(f"✗ {error_msg[:30]}")
                except socket.timeout:
                    print("✗ Timeout")
                except Exception as e:
                    print(f"✗ {str(e)[:30]}")
            
            if not found:
                print("  ✗ Ninguna combinación funcionó")
            
            print()
        
        socket.setdefaulttimeout(old_timeout)
        Database.close_connections()
        
        print("=" * 60)
        print(f"Resultado: {success_count}/{len(accounts)} cuentas conectadas")
        print("=" * 60)
        
        if working_method:
            print(f"\n✓ MÉTODO EXITOSO ENCONTRADO:")
            print(f"   Usuario: {working_method['user']}")
            print(f"   Contraseña: {'***' if working_method['pass'] else 'VACÍA'}")
            print(f"\nAhora necesitamos actualizar el provider_config de todas")
            print(f"las cuentas para usar este formato.")
        
        return success_count > 0
        
    except Exception as e:
        print(f"✗ Error fatal: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        Database.close_connections()

if __name__ == '__main__':
    sys.exit(0 if test_with_old_passwords() else 1)
