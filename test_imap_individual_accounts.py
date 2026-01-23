#!/usr/bin/env python3
"""
GAC - Test de conexión IMAP con cuentas individuales usando servidor local
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

def test_individual_accounts():
    """Probar conexión IMAP con cuentas individuales usando servidor local"""
    print("=" * 60)
    print("GAC - Test de Conexión IMAP (Cuentas Individuales - Servidor Local)")
    print("=" * 60 + "\n")
    
    try:
        accounts = EmailAccountRepository.find_by_type('imap')
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        # Probar las primeras 5 cuentas
        test_accounts = accounts[:5]
        print(f"Probando {len(test_accounts)} cuentas con servidor local\n")
        
        local_server = 'premium211.web-hosting.com'
        port = 993
        
        old_timeout = socket.getdefaulttimeout()
        socket.setdefaulttimeout(10)
        
        success_count = 0
        
        for account in test_accounts:
            account_email = account['email']
            provider_config = account.get('provider_config', '{}')
            
            print(f"Probando: {account_email}")
            print("-" * 60)
            
            try:
                config = json.loads(provider_config) if provider_config else {}
                username = config.get('imap_user') or account_email
                password = config.get('imap_password') or ''
                
                if not password:
                    print("  ✗ Contraseña no configurada")
                    continue
                
                print(f"  Usuario: {username}")
                print(f"  Servidor: {local_server}")
                print(f"  Intentando conectar...", end=" ")
                
                # Intentar con servidor local
                mail = imaplib.IMAP4_SSL(local_server, port)
                mail.login(username, password)
                mail.select('INBOX')
                status, messages = mail.search(None, 'ALL')
                
                if status == 'OK':
                    email_ids = messages[0].split()
                    print(f"✓ CONECTADO")
                    print(f"  ✓ Total de emails: {len(email_ids)}")
                    
                    # Leer el último email como muestra
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
                            print(f"  ✓ Último email: {subject[:50]}")
                    
                    mail.close()
                    mail.logout()
                    success_count += 1
                else:
                    print("✗ Error al buscar emails")
                    mail.logout()
                
            except imaplib.IMAP4.error as e:
                error_msg = str(e)
                if 'AUTHENTICATIONFAILED' in error_msg:
                    print("✗ Error de autenticación")
                    print("  Posible causa: Contraseña incorrecta o cuenta no existe en servidor local")
                else:
                    print(f"✗ Error IMAP: {error_msg[:50]}")
            except socket.error as e:
                print(f"✗ Error de red: {e}")
            except Exception as e:
                print(f"✗ Error: {str(e)[:50]}")
            
            print()
        
        socket.setdefaulttimeout(old_timeout)
        
        print("=" * 60)
        print(f"Resultado: {success_count}/{len(test_accounts)} cuentas conectadas")
        print("=" * 60)
        
        if success_count > 0:
            print("\n✓ ¡ÉXITO! Al menos algunas cuentas funcionan con servidor local")
            print("\nRecomendación:")
            print("1. Actualiza el provider_config para usar 'premium211.web-hosting.com'")
            print("2. Verifica que todas las contraseñas estén correctas")
        else:
            print("\n⚠ ADVERTENCIA: Ninguna cuenta pudo conectarse")
            print("Posibles causas:")
            print("1. Las contraseñas en la BD no coinciden con las del servidor")
            print("2. Las cuentas no existen en el servidor IMAP local")
            print("3. El servidor requiere un formato diferente de usuario")
        
        return success_count > 0
        
    except Exception as e:
        print(f"✗ Error fatal: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        Database.close_connections()

if __name__ == '__main__':
    sys.exit(0 if test_individual_accounts() else 1)
