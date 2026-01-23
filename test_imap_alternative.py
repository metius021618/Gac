#!/usr/bin/env python3
"""
GAC - Test de Conexión IMAP con Configuraciones Alternativas
Prueba diferentes puertos y métodos de conexión
"""

import sys
import os
import json
import imaplib
import socket
from dotenv import load_dotenv

# Encontrar el directorio raíz del proyecto
script_path = os.path.abspath(__file__)
project_root = os.path.dirname(script_path)

# Cargar .env desde el directorio raíz
env_path = os.path.join(project_root, '.env')
if os.path.exists(env_path):
    load_dotenv(env_path)

cron_dir = os.path.join(project_root, 'cron')
sys.path.insert(0, project_root)
os.chdir(cron_dir)

from cron.database import Database, USE_PYMYSQL
from cron.repositories import EmailAccountRepository

def test_port(host, port, use_ssl=True):
    """Probar si un puerto está accesible"""
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(5)
        result = sock.connect_ex((host, port))
        sock.close()
        return result == 0
    except:
        return False

def test_imap_connection(server, port, encryption, username, password):
    """Probar conexión IMAP con diferentes configuraciones"""
    try:
        if encryption == 'ssl':
            mail = imaplib.IMAP4_SSL(server, port)
        elif encryption == 'tls':
            mail = imaplib.IMAP4(server, port)
            mail.starttls()
        else:
            mail = imaplib.IMAP4(server, port)
        
        mail.login(username, password)
        mail.select('INBOX')
        status, messages = mail.search(None, 'ALL')
        
        if status == 'OK':
            email_ids = messages[0].split()
            mail.close()
            mail.logout()
            return True, len(email_ids)
        
        mail.logout()
        return False, 0
    except Exception as e:
        return False, str(e)

def main():
    print("=" * 60)
    print("GAC - Test de Conexión IMAP (Configuraciones Alternativas)")
    print("=" * 60 + "\n")
    
    try:
        # Obtener una cuenta de prueba
        accounts = EmailAccountRepository.find_by_type('imap')
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        account = accounts[0]
        account_email = account['email']
        provider_config = account.get('provider_config', '{}')
        
        print(f"Probando cuenta: {account_email}\n")
        
        # Parsear configuración
        config = json.loads(provider_config) if provider_config else {}
        username = config.get('imap_user') or account_email
        password = config.get('imap_password') or ''
        
        if not password:
            print("✗ Contraseña no configurada")
            return False
        
        # Probar diferentes configuraciones
        configs_to_test = [
            ("Gmail IMAP SSL (993)", "imap.gmail.com", 993, "ssl"),
            ("Gmail IMAP STARTTLS (143)", "imap.gmail.com", 143, "tls"),
            ("Gmail IMAP sin SSL (143)", "imap.gmail.com", 143, "none"),
        ]
        
        print("Probando conectividad de puertos:")
        print("-" * 60)
        for name, server, port, _ in configs_to_test:
            accessible = test_port(server, port)
            status = "✓ ACCESIBLE" if accessible else "✗ BLOQUEADO"
            print(f"  {name}: {status}")
        print()
        
        print("Probando conexiones IMAP:")
        print("-" * 60)
        
        success = False
        for name, server, port, encryption in configs_to_test:
            print(f"\n{name}...")
            try:
                result, data = test_imap_connection(server, port, encryption, username, password)
                if result:
                    print(f"  ✓ CONEXIÓN EXITOSA")
                    print(f"  ✓ Emails en INBOX: {data}")
                    success = True
                    print(f"\n  ⚠ RECOMENDACIÓN: Actualiza el provider_config para usar:")
                    print(f"     imap_server: {server}")
                    print(f"     imap_port: {port}")
                    print(f"     imap_encryption: {encryption}")
                    break
                else:
                    print(f"  ✗ FALLO: {data}")
            except Exception as e:
                print(f"  ✗ ERROR: {e}")
        
        print("\n" + "=" * 60)
        if success:
            print("✓ Se encontró una configuración que funciona")
        else:
            print("✗ Ninguna configuración funcionó")
            print("\nPosibles soluciones:")
            print("1. Contactar al hosting para desbloquear puerto 993")
            print("2. Verificar credenciales de Gmail")
            print("3. Usar Gmail API en lugar de IMAP")
        print("=" * 60)
        
        return success
        
    except Exception as e:
        print(f"✗ Error fatal: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        Database.close_connections()

if __name__ == '__main__':
    sys.exit(0 if main() else 1)
