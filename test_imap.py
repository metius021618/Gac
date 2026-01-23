#!/usr/bin/env python3
"""
GAC - Script de Prueba de Conexión IMAP
Versión que funciona desde cualquier directorio
"""

import sys
import os
import json
import imaplib
import email
from email.header import decode_header

# Encontrar el directorio raíz del proyecto
script_path = os.path.abspath(__file__)
project_root = os.path.dirname(script_path)
cron_dir = os.path.join(project_root, 'cron')

# Agregar directorio raíz al path
sys.path.insert(0, project_root)

# Cambiar al directorio cron para que los imports funcionen
os.chdir(cron_dir)

# Ahora importar módulos
from cron.database import Database
from cron.repositories import EmailAccountRepository

def test_imap():
    """Probar conexión IMAP"""
    print("=" * 60)
    print("GAC - Test de Conexión IMAP")
    print("=" * 60 + "\n")
    
    try:
        # Obtener cuentas
        accounts = EmailAccountRepository.find_by_type('imap')
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        print(f"Encontradas {len(accounts)} cuenta(s) IMAP\n")
        
        success_count = 0
        
        for account in accounts[:3]:  # Probar solo las primeras 3
            account_id = account['id']
            account_email = account['email']
            provider_config = account.get('provider_config', '{}')
            
            print(f"Probando cuenta: {account_email} (ID: {account_id})")
            print("-" * 60)
            
            # Parsear configuración
            try:
                config = json.loads(provider_config) if provider_config else {}
                server = config.get('imap_server') or config.get('host', '')
                port = config.get('imap_port') or config.get('port', 993)
                encryption = config.get('imap_encryption') or config.get('encryption', 'ssl')
                username = config.get('imap_user') or account_email
                password = config.get('imap_password') or config.get('password', '')
                
                print(f"  Servidor: {server}")
                print(f"  Puerto: {port}")
                print(f"  Usuario: {username}")
                print(f"  Contraseña: {'***' if password else 'NO CONFIGURADA'}")
                
                if not server or not username or not password:
                    print("  ✗ Configuración incompleta")
                    continue
                
            except Exception as e:
                print(f"  ✗ Error al parsear configuración: {e}")
                print(f"  Config: {provider_config[:100]}")
                continue
            
            # Intentar conectar
            try:
                print("\n  Intentando conectar...")
                
                if encryption == 'ssl':
                    mail = imaplib.IMAP4_SSL(server, port)
                else:
                    mail = imaplib.IMAP4(server, port)
                    if encryption == 'tls':
                        mail.starttls()
                
                mail.login(username, password)
                mail.select('INBOX')
                
                # Buscar emails
                status, messages = mail.search(None, 'ALL')
                
                if status != 'OK':
                    raise Exception("Error al buscar emails")
                
                email_ids = messages[0].split()
                print(f"  ✓ Conexión exitosa!")
                print(f"  ✓ Total de emails en INBOX: {len(email_ids)}")
                
                # Leer los últimos 3 emails
                if email_ids:
                    print(f"\n  Últimos 3 emails:")
                    for email_id in reversed(email_ids[-3:]):
                        status, msg_data = mail.fetch(email_id, '(RFC822)')
                        if status == 'OK':
                            email_body = msg_data[0][1]
                            msg = email.message_from_bytes(email_body)
                            subject = decode_header(msg['Subject'] or '')[0][0]
                            if isinstance(subject, bytes):
                                subject = subject.decode('utf-8', errors='ignore')
                            print(f"    - {subject[:50]}")
                
                mail.close()
                mail.logout()
                
                success_count += 1
                print()
                
            except Exception as e:
                print(f"  ✗ Error al conectar: {e}")
                print()
                continue
        
        print("=" * 60)
        print(f"Resultado: {success_count}/{min(len(accounts), 3)} cuenta(s) conectada(s)")
        print("=" * 60)
        
        return success_count > 0
        
    except Exception as e:
        print(f"✗ Error fatal: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        Database.close_connections()


if __name__ == '__main__':
    success = test_imap()
    sys.exit(0 if success else 1)
