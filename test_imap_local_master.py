#!/usr/bin/env python3
"""
GAC - Test de Conexión IMAP usando cuenta maestra del servidor local
Prueba con streaming@pocoyoni.com como el sistema anterior
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

def test_imap_master():
    """Probar conexión IMAP usando cuenta maestra (como sistema anterior)"""
    print("=" * 60)
    print("GAC - Test de Conexión IMAP (Cuenta Maestra)")
    print("=" * 60 + "\n")
    
    # Configuración del sistema anterior
    local_server = 'premium211.web-hosting.com'
    port = 993
    master_user = 'streaming@pocoyoni.com'
    master_password = 'D3b+Vln0tj0Q'  # Contraseña del sistema anterior
    
    print(f"Servidor: {local_server}")
    print(f"Puerto: {port}")
    print(f"Usuario: {master_user}\n")
    
    print("Intentando conectar...", end=" ")
    
    old_timeout = socket.getdefaulttimeout()
    socket.setdefaulttimeout(10)
    
    try:
        mail = imaplib.IMAP4_SSL(local_server, port)
        mail.login(master_user, master_password)
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
            print("✓ ¡ÉXITO! La cuenta maestra funciona")
            print("=" * 60)
            print("\nNOTA: El servidor local funciona, pero cada cuenta")
            print("individual puede necesitar sus propias credenciales.")
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
        print("\nPosibles causas:")
        print("1. La contraseña de la cuenta maestra cambió")
        print("2. El servidor requiere autenticación diferente")
        print("3. Cada cuenta necesita sus propias credenciales")
        return False
    except Exception as e:
        print(f"✗ Error: {e}")
        return False
    finally:
        socket.setdefaulttimeout(old_timeout)

if __name__ == '__main__':
    sys.exit(0 if test_imap_master() else 1)
