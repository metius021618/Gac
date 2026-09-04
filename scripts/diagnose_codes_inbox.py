#!/usr/bin/env python3
"""Diagnóstico: asuntos + códigos recientes."""
import os
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.chdir(ROOT)

from cron.database import Database

db = Database.get_connection()
cur = db.cursor()

print("=== SUBJECTS BY CATEGORY ===")
cur.execute("SELECT category, COUNT(*) c FROM email_subjects WHERE active=1 GROUP BY category")
for row in cur.fetchall():
    print(row)

print("=== NETFLIX SUBJECTS ===")
cur.execute(
    "SELECT es.id, es.category, es.subject_line "
    "FROM email_subjects es JOIN platforms p ON p.id=es.platform_id "
    "WHERE es.active=1 AND LOWER(p.name)='netflix' ORDER BY es.category, es.id"
)
for row in cur.fetchall():
    print(row)

print("=== modo_hogar / modo_viaje ===")
cur.execute(
    "SELECT es.id, es.category, es.subject_line, p.name "
    "FROM email_subjects es JOIN platforms p ON p.id=es.platform_id "
    "WHERE es.active=1 AND es.category IN ('modo_hogar','modo_viaje') "
    "ORDER BY es.category, es.id"
)
for row in cur.fetchall():
    print(row)

print("=== COUNTS ===")
for label, interval in [("1h", "INTERVAL 1 HOUR"), ("6h", "INTERVAL 6 HOUR"), ("24h", "INTERVAL 1 DAY"), ("7d", "INTERVAL 7 DAY")]:
    cur.execute(f"SELECT COUNT(*) FROM codes WHERE received_at >= NOW() - {interval}")
    print(label, cur.fetchone()[0])

print("=== LAST 25 CODES ===")
cur.execute(
    "SELECT id, received_at, origin, recipient_email, LEFT(subject,90) "
    "FROM codes ORDER BY id DESC LIMIT 25"
)
for row in cur.fetchall():
    print(row)

print("=== GMAIL MATRIX / ACCOUNTS ===")
try:
    cur.execute(
        "SELECT ea.id, ea.email, ea.enabled, ea.last_sync_at, ea.sync_status, LEFT(COALESCE(ea.error_message,''),160) "
        "FROM gmail_matrix gm JOIN email_accounts ea ON ea.id=gm.email_account_id WHERE gm.id=1"
    )
    print("matrix", cur.fetchone())
except Exception as e:
    print("matrix_err", e)

cur.execute(
    "SELECT id, type, email, enabled, last_sync_at, sync_status, LEFT(COALESCE(error_message,''),120) "
    "FROM email_accounts ORDER BY COALESCE(last_sync_at,'1970-01-01') DESC LIMIT 8"
)
for row in cur.fetchall():
    print(row)

cur.close()
print("DONE")
