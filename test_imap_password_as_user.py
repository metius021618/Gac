#!/usr/bin/env python3
"""
GAC - Test de conexión IMAP usando password como usuario
Basado en la estructura de la BD antigua
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

def test_password_as_user():
    """Probar usando password como usuario IMAP"""
    print("=" * 60)
    print("GAC - Test IMAP (Password como Usuario)")
    print("=" * 60 + "\n")
    
    try:
        # Obtener cuentas de la BD nueva
        accounts = EmailAccountRepository.find_by_type('imap')[:5]
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        # Las contraseñas ya están en provider_config, pero necesitamos
        # obtener los "passwords" de la BD antigua que podrían ser usuarios
        # Por ahora, usaremos las contraseñas que ya tenemos y probaremos
        # diferentes formatos
        
        # Obtener passwords de provider_config (estos son los que migramos)
        account_passwords = {}
        for acc in accounts:
            config = json.loads(acc.get('provider_config', '{}'))
            account_passwords[acc['email']] = config.get('imap_password', '')
        
        local_server = 'premium211.web-hosting.com'
        port = 993
        master_password = 'D3b+Vln0tj0Q'  # Contraseña de la cuenta maestra
        
        old_timeout = socket.getdefaulttimeout()
        socket.setdefaulttimeout(15)
        
        success_count = 0
        
        for account in accounts:
            account_email = account['email']
            provider_config = account.get('provider_config', '{}')
            config = json.loads(provider_config) if provider_config else {}
            
            # Obtener password del provider_config
            current_password = account_passwords.get(account_email, '')
            
            print(f"Probando: {account_email}")
            print("-" * 60)
            
            if not current_password:
                print("  ✗ No se encontró contraseña en provider_config")
                print()
                continue
            
            # El "password" de la BD antigua podría ser el usuario
            # Extraer parte antes del @ del email como posible usuario alternativo
            email_user = account_email.split('@')[0]  # ej: "cine003"
            
            # Probar diferentes combinaciones
            # Nota: El "password" de la BD antigua (ENRIQUEBR, JOSEAGVE) podría ser el usuario
            combinations = [
                {
                    'name': 'Email como usuario + Contraseña actual',
                    'user': account_email,
                    'pass': current_password
                },
                {
                    'name': 'Email como usuario + Contraseña maestra',
                    'user': account_email,
                    'pass': master_password
                },
                {
                    'name': 'Usuario del email (sin @) + Contraseña actual',
                    'user': email_user,
                    'pass': current_password
                },
                {
                    'name': 'Usuario del email + Contraseña maestra',
                    'user': email_user,
                    'pass': master_password
                },
                {
                    'name': 'Contraseña como usuario + Contraseña actual',
                    'user': current_password,
                    'pass': current_password
                },
                {
                    'name': 'Contraseña como usuario + Contraseña maestra',
                    'user': current_password,
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
                        
                        print(f"\n  ✓ MÉTODO EXITOSO: {combo['name']}")
                        print(f"     Usuario: {combo['user']}")
                        print(f"     Contraseña: {'***' if combo['pass'] else 'VACÍA'}")
                        
                        success_count += 1
                        found = True
                        break
                    
                except imaplib.IMAP4.error:
                    print("✗")
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
        
        if success_count > 0:
            print("\n✓ ¡ÉXITO! Se encontró el método correcto")
            print("\nAhora necesitamos actualizar el provider_config con")
            print("el formato de usuario/contraseña que funcionó.")
        
        return success_count > 0
        
    except Exception as e:
        print(f"✗ Error fatal: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        Database.close_connections()

if __name__ == '__main__':
    sys.exit(0 if test_password_as_user() else 1)
