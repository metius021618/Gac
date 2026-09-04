#!/usr/bin/env python3
import os
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.chdir(ROOT)

from cron.database import Database

db = Database.get_connection()
cur = db.cursor()

print("=== SEARCH temporal / hogar / viaje subjects (all active flags) ===")
cur.execute(
    "SELECT id, active, category, subject_line FROM email_subjects "
    "WHERE subject_line LIKE %s OR subject_line LIKE %s OR subject_line LIKE %s "
    "OR subject_line LIKE %s OR subject_line LIKE %s OR subject_line LIKE %s "
    "ORDER BY id",
    ("%temporal%", "%Hogar%", "%hogar%", "%viaje%", "%Household%", "%Hola%"),
)
for row in cur.fetchall():
    print(row)

print("=== codes subject temporal last rows ===")
cur.execute(
    "SELECT id, received_at, recipient_email, LEFT(subject,100) FROM codes "
    "WHERE subject LIKE %s ORDER BY id DESC LIMIT 15",
    ("%temporal%",),
)
for row in cur.fetchall():
    print(row)

print("=== codes subject actualizar hogar last rows ===")
cur.execute(
    "SELECT id, received_at, recipient_email, LEFT(subject,100) FROM codes "
    "WHERE subject LIKE %s OR subject LIKE %s OR subject LIKE %s ORDER BY id DESC LIMIT 15",
    ("%actualizar tu Hogar%", "%Household%", "%Hogar con Netflix%"),
)
for row in cur.fetchall():
    print(row)

print("=== category counts ===")
cur.execute("SELECT category, COUNT(*) FROM email_subjects WHERE active=1 GROUP BY category")
for row in cur.fetchall():
    print(row)

cur.close()
print("DONE")
