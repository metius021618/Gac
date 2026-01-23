#!/usr/bin/env python3
"""
GAC - Script de Prueba de Conexión IMAP
Verifica que las cuentas de email puedan conectarse y leer correos
"""

import sys
import os
import json

# Agregar directorio actual al path
current_dir = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, current_dir)

# Cambiar al directorio del script para que los imports funcionen
os.chdir(current_dir)

from cron.database import Database
from cron.repositories import EmailAccountRepository
from cron.imap_service import ImapService

def test_imap_connection(account_id=None):
    """Probar conexión IMAP de una cuenta específica o todas"""
    print("=" * 60)
    print("GAC - Test de Conexión IMAP")
    print("=" * 60 + "\n")
    
    try:
        # Obtener cuentas
        if account_id:
            accounts = [EmailAccountRepository.find_by_id(account_id)]
            if not accounts[0]:
                print(f"✗ Cuenta con ID {account_id} no encontrada")
                return False
        else:
            accounts = EmailAccountRepository.find_by_type('imap')
        
        if not accounts:
            print("✗ No hay cuentas IMAP configuradas")
            return False
        
        print(f"Encontradas {len(accounts)} cuenta(s) IMAP\n")
        
        imap_service = ImapService()
        success_count = 0
        
        for account in accounts:
            account_id = account['id']
            account_email = account['email']
            provider_config = account.get('provider_config', '{}')
            
            print(f"Probando cuenta: {account_email} (ID: {account_id})")
            print("-" * 60)
            
            # Mostrar configuración (sin contraseña)
            try:
                config = json.loads(provider_config) if provider_config else {}
                print(f"  Servidor IMAP: {config.get('imap_server', 'NO CONFIGURADO')}")
                print(f"  Puerto: {config.get('imap_port', 'NO CONFIGURADO')}")
                print(f"  Encriptación: {config.get('imap_encryption', 'NO CONFIGURADO')}")
                print(f"  Usuario: {config.get('imap_user', 'NO CONFIGURADO')}")
                print(f"  Contraseña: {'***' if config.get('imap_password') else 'NO CONFIGURADA'}")
            except Exception as e:
                print(f"  ✗ Error al parsear configuración: {e}")
                continue
            
            # Intentar conectar
            try:
                print("\n  Intentando conectar...")
                emails = imap_service.read_account(account)
                print(f"  ✓ Conexión exitosa!")
                print(f"  ✓ Emails leídos: {len(emails)}")
                
                if emails:
                    print(f"\n  Primeros 3 emails:")
                    for email_data in emails[:3]:
                        print(f"    - Asunto: {email_data.get('subject', 'Sin asunto')[:50]}")
                        print(f"      De: {email_data.get('from', 'Desconocido')}")
                        print(f"      Fecha: {email_data.get('date', 'Desconocida')}")
                
                success_count += 1
                print()
                
            except Exception as e:
                print(f"  ✗ Error al conectar: {e}")
                print()
                continue
        
        print("=" * 60)
        print(f"Resultado: {success_count}/{len(accounts)} cuenta(s) conectada(s) exitosamente")
        print("=" * 60)
        
        return success_count > 0
        
    except Exception as e:
        print(f"✗ Error fatal: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        Database.close_connections()


def main():
    """Función principal"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Probar conexión IMAP')
    parser.add_argument('--account-id', type=int, help='ID de cuenta específica a probar')
    args = parser.parse_args()
    
    success = test_imap_connection(args.account_id)
    sys.exit(0 if success else 1)


if __name__ == '__main__':
    main()
