#!/usr/bin/env python3
"""
GAC - Test de Conexión IMAP con Métodos Alternativos
Prueba diferentes puertos y métodos de conexión
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

def test_imap_connection(server, port, encryption, username, password, timeout=10):
    """Probar conexión IMAP con diferentes métodos"""
    try:
        print(f"    Intentando {encryption} en puerto {port}...", end=" ")
        
        if encryption == 'ssl':
            mail = imaplib.IMAP4_SSL(server, port, timeout=timeout)
        elif encryption == 'tls':
            mail = imaplib.IMAP4(server, port, timeout=timeout)
            mail.starttls()
        else:
            mail = imaplib.IMAP4(server, port, timeout=timeout)
        
        mail.login(username, password)
        mail.select('INBOX')
        status, messages = mail.search(None, 'ALL')
        
        if status == 'OK':
            email_ids = messages[0].split()
            mail.close()
            mail.logout()
            return True, len(email_ids)
        
        mail.logout()
        return False, "Error al buscar emails"
        
    except socket.timeout:
        return False, "Timeout"
    except socket.error as e:
        return False, f"Error de red: {e}"
    except imaplib.IMAP4.error as e:
        return False, f"Error IMAP: {e}"
    except Exception as e:
        return False, str(e)

def main():
    print("=" * 60)
    print("GAC - Test de Conexión IMAP (Métodos Alternativos)")
    print("=" * 60 + "\n")
    
    try:
        accounts = EmailAccountRepository.find_by_type('imap')
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        # Probar solo la primera cuenta con diferentes métodos
        account = accounts[0]
        account_email = account['email']
        provider_config = account.get('provider_config', '{}')
        
        print(f"Probando cuenta: {account_email}")
        print("-" * 60)
        
        config = json.loads(provider_config) if provider_config else {}
        server = config.get('imap_server') or 'imap.gmail.com'
        username = config.get('imap_user') or account_email
        password = config.get('imap_password') or ''
        
        if not password:
            print("✗ Contraseña no configurada")
            return False
        
        print(f"Servidor: {server}")
        print(f"Usuario: {username}\n")
        
        # Probar diferentes métodos
        methods = [
            ('ssl', 993, 'SSL en puerto 993 (estándar)'),
            ('tls', 143, 'STARTTLS en puerto 143'),
            ('ssl', 465, 'SSL en puerto 465 (alternativo)'),
        ]
        
        success = False
        
        for encryption, port, description in methods:
            print(f"  {description}:")
            result, data = test_imap_connection(server, port, encryption, username, password)
            
            if result:
                print(f"    ✓ CONECTADO - {data} emails encontrados")
                success = True
                print(f"\n  ✓ Método exitoso: {encryption} en puerto {port}")
                break
            else:
                print(f"    ✗ FALLO - {data}")
        
        print("\n" + "=" * 60)
        
        if success:
            print("✓ Se encontró un método de conexión funcional")
            print("\nRecomendación: Actualiza el provider_config para usar")
            print("el método que funcionó.")
        else:
            print("✗ No se pudo conectar con ningún método")
            print("\nPosibles soluciones:")
            print("1. Contacta al hosting para desbloquear el puerto 993")
            print("2. Verifica si hay un proxy configurado")
            print("3. Verifica la configuración del firewall en cPanel")
            print("4. Considera usar un servidor IMAP diferente")
        
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
