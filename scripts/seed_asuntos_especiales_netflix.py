#!/usr/bin/env python3
"""
Siembra reglas Netflix de asuntos especiales (idempotente).
Uso: python3 scripts/seed_asuntos_especiales_netflix.py
"""
import os
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.chdir(ROOT)

from cron.database import Database

SUBJECT = "Actualización importante de la cuenta"

RULES = [
    {
        "category": "especial_leer",
        "special_action": "leer",
        "body_match": "Recibimos una solicitud para cambiar la información de tu cuenta",
    },
    {
        "category": "especial_no_leer",
        "special_action": "no_leer",
        "body_match": "finalizar una compra en tu cuenta",
    },
]


def main():
    db = Database.get_connection()
    cur = db.cursor()
    cur.execute("SELECT id FROM platforms WHERE LOWER(name)='netflix' LIMIT 1")
    row = cur.fetchone()
    if not row:
        print("FAIL: plataforma netflix no encontrada")
        sys.exit(1)
    platform_id = row[0] if not isinstance(row, dict) else row["id"]

    for rule in RULES:
        cur.execute(
            """
            SELECT id FROM email_subjects
            WHERE platform_id=%s AND category=%s AND active=1
              AND subject_line=%s AND body_match=%s
            LIMIT 1
            """,
            (platform_id, rule["category"], SUBJECT, rule["body_match"]),
        )
        if cur.fetchone():
            print("SKIP exists:", rule["category"])
            continue
        cur.execute(
            """
            INSERT INTO email_subjects
              (platform_id, subject_line, category, body_match, special_action, active)
            VALUES (%s, %s, %s, %s, %s, 1)
            """,
            (
                platform_id,
                SUBJECT,
                rule["category"],
                rule["body_match"],
                rule["special_action"],
            ),
        )
        print("INSERT", rule["category"], "id=", cur.lastrowid)

    db.commit()
    cur.close()
    print("SEED_OK")


if __name__ == "__main__":
    main()
