#!/usr/bin/env python3
"""
GAC - Test de acceso a buzones específicos con cuenta maestra
"""

import sys
import os
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

def test_master_access_mailboxes():
    """Probar acceso a buzones específicos con cuenta maestra"""
    print("=" * 60)
    print("GAC - Test de Acceso a Buzones con Cuenta Maestra")
    print("=" * 60 + "\n")
    
    # Configuración de cuenta maestra
    local_server = 'premium211.web-hosting.com'
    port = 993
    master_user = 'streaming@pocoyoni.com'
    master_password = 'D3b+Vln0tj0Q'
    
    # Obtener algunas cuentas de la BD
    try:
        accounts = EmailAccountRepository.find_by_type('imap')[:5]
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        print(f"Probando acceso a {len(accounts)} buzones con cuenta maestra\n")
        
        old_timeout = socket.getdefaulttimeout()
        socket.setdefaulttimeout(10)
        
        try:
            mail = imaplib.IMAP4_SSL(local_server, port)
            mail.login(master_user, master_password)
            
            success_count = 0
            
            for account in accounts:
                account_email = account['email']
                print(f"Probando buzón: {account_email}...", end=" ")
                
                try:
                    # Intentar acceder al buzón específico
                    # En algunos servidores IMAP, puedes acceder con: usuario/buzon
                    # O usando el formato: INBOX.usuario
                    mailboxes_to_try = [
                        f"INBOX.{account_email}",
                        account_email,
                        f"{master_user}/{account_email}",
                        f"INBOX/{account_email}"
                    ]
                    
                    found = False
                    for mailbox in mailboxes_to_try:
                        try:
                            status, data = mail.select(mailbox)
                            if status == 'OK':
                                status, messages = mail.search(None, 'ALL')
                                if status == 'OK':
                                    email_ids = messages[0].split()
                                    print(f"✓ CONECTADO ({len(email_ids)} emails)")
                                    success_count += 1
                                    found = True
                                    break
                        except:
                            continue
                    
                    if not found:
                        # Intentar listar buzones disponibles
                        status, mailboxes = mail.list()
                        if status == 'OK':
                            print(f"✗ No encontrado (buzones disponibles: {len(mailboxes)} total)")
                        else:
                            print("✗ No encontrado")
                    
                except Exception as e:
                    print(f"✗ Error: {str(e)[:50]}")
            
            mail.logout()
            
            print("\n" + "=" * 60)
            print(f"Resultado: {success_count}/{len(accounts)} buzones accesibles")
            print("=" * 60)
            
            if success_count == 0:
                print("\nNOTA: El servidor puede requerir que cada cuenta")
                print("use sus propias credenciales, o puede usar un formato")
                print("diferente para acceder a buzones específicos.")
            
            return success_count > 0
            
        except Exception as e:
            print(f"✗ Error al conectar: {e}")
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
    sys.exit(0 if test_master_access_mailboxes() else 1)
