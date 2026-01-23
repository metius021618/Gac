#!/usr/bin/env python3
"""
GAC - Test de Conectividad de Red
Verifica si el servidor puede conectarse a servicios externos
"""

import socket
import sys

def test_connection(host, port, timeout=5):
    """Probar conexión a un host:puerto"""
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(timeout)
        result = sock.connect_ex((host, port))
        sock.close()
        return result == 0
    except Exception as e:
        return False, str(e)

def main():
    print("=" * 60)
    print("GAC - Test de Conectividad de Red")
    print("=" * 60 + "\n")
    
    tests = [
        ("Google DNS", "8.8.8.8", 53),
        ("Google IMAP", "imap.gmail.com", 993),
        ("Google SMTP", "smtp.gmail.com", 587),
        ("Localhost", "localhost", 3306),
    ]
    
    results = []
    
    for name, host, port in tests:
        print(f"Probando {name} ({host}:{port})...", end=" ")
        success = test_connection(host, port)
        if success:
            print("✓ CONECTADO")
            results.append((name, True))
        else:
            print("✗ NO CONECTADO")
            results.append((name, False))
    
    print("\n" + "=" * 60)
    print("Resumen:")
    print("=" * 60)
    
    for name, success in results:
        status = "✓ OK" if success else "✗ FALLO"
        print(f"  {name}: {status}")
    
    # Verificar si hay acceso a internet
    all_failed = all(not success for _, success in results if "Localhost" not in _)
    if all_failed:
        print("\n⚠ ADVERTENCIA: El servidor no puede conectarse a servicios externos.")
        print("  Posibles causas:")
        print("  - Firewall bloqueando conexiones salientes")
        print("  - Servidor sin acceso a internet")
        print("  - Restricciones de red del hosting")
        print("\n  Solución: Contacta al administrador del servidor o verifica")
        print("  la configuración de firewall/proxy.")
    
    return 0 if all(success for _, success in results) else 1

if __name__ == '__main__':
    sys.exit(main())
