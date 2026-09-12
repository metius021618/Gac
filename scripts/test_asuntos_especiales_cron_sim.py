#!/usr/bin/env python3
"""
Simula varios correos Netflix contra el filtro real (BD) y reporta acciones.
Útil para validar el cron sin esperar Gmail real.

Uso en servidor: python3 scripts/test_asuntos_especiales_cron_sim.py
"""
import os
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.chdir(ROOT)

from cron.email_filter import EmailFilterService

FIXTURES = [
    {
        "name": "compra",
        "subject": "Actualización importante de la cuenta",
        "body": "Hola. Estás a punto de finalizar una compra en tu cuenta de Netflix. Revisa el monto.",
        "expect": "discard",
    },
    {
        "name": "cambio_cuenta",
        "subject": "Actualización importante de la cuenta",
        "body": "Recibimos una solicitud para cambiar la información de tu cuenta. Si no fuiste tú, cierra otras sesiones.",
        "expect": "save_special",
    },
    {
        "name": "otp_normal",
        "subject": "Tu código de acceso temporal de Netflix",
        "body": "Tu código de acceso temporal es 482913.",
        "expect": "save",
    },
    {
        "name": "mismo_asunto_otro_cuerpo",
        "subject": "Actualización importante de la cuenta",
        "body": "Este es un aviso genérico sin coincidencia de reglas especiales.",
        "expect": "save",
    },
]


def main():
    svc = EmailFilterService()
    print("special_rules=", len(svc.special_rules))
    failed = 0
    for fx in FIXTURES:
        email = {
            "subject": fx["subject"],
            "body_html": f"<html><body><p>{fx['body']}</p></body></html>",
        }
        filtered = svc.filter_by_subject([email])
        if not filtered:
            action = None
        else:
            decision = svc.classify_email(filtered[0])
            action = decision.get("action")
        ok = action == fx["expect"]
        status = "OK" if ok else "FAIL"
        if not ok:
            failed += 1
        print(f"[{status}] {fx['name']}: action={action} expect={fx['expect']}")
    if failed:
        print(f"RESULT=FAIL ({failed})")
        sys.exit(1)
    print("RESULT=OK")


if __name__ == "__main__":
    main()
