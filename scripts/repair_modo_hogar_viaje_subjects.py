#!/usr/bin/env python3
"""Reparar categorías modo_hogar / modo_viaje en producción."""
import os
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.chdir(ROOT)

from cron.database import Database

db = Database.get_connection()
cur = db.cursor()

# 1) Reactivar asunto Código Temporal
cur.execute(
    "UPDATE email_subjects SET active=1, category='modo_hogar', updated_at=NOW() "
    "WHERE subject_line=%s",
    ("Tu código de acceso temporal de Netflix",),
)
print("reactivated_temporal_rows=", cur.rowcount)

# Si quedó duplicado activo en general + modo_hogar, dejar solo modo_hogar activo
cur.execute(
    "SELECT id, active, category FROM email_subjects WHERE subject_line=%s ORDER BY id",
    ("Tu código de acceso temporal de Netflix",),
)
rows = cur.fetchall()
print("temporal_rows=", rows)
active_hogar = [r for r in rows if r[2] == "modo_hogar"]
if active_hogar:
    keep_id = active_hogar[0][0]
    cur.execute(
        "UPDATE email_subjects SET active=1, category='modo_hogar', updated_at=NOW() WHERE id=%s",
        (keep_id,),
    )
    cur.execute(
        "UPDATE email_subjects SET active=0, updated_at=NOW() "
        "WHERE subject_line=%s AND id<>%s",
        ("Tu código de acceso temporal de Netflix", keep_id),
    )
    print("kept_modo_hogar_id=", keep_id)

# 2) Asuntos de Actualizar Hogar (Netflix household) -> modo_viaje
household = [
    "Importante: Cómo actualizar tu Hogar con Netflix",
    "Importante: Cómo cambiar tu hogar Netflix",
    "Important: How to update your Netflix Household",
]
for subj in household:
    cur.execute(
        "UPDATE email_subjects SET category='modo_viaje', active=1, updated_at=NOW() "
        "WHERE subject_line=%s AND active=1",
        (subj,),
    )
    print("moved_to_modo_viaje:", subj, "rows=", cur.rowcount)

# 3) Desactivar basura "Hola" en modo_viaje si existe
cur.execute(
    "UPDATE email_subjects SET active=0, updated_at=NOW() "
    "WHERE category='modo_viaje' AND subject_line=%s",
    ("Hola",),
)
print("disabled_hola=", cur.rowcount)

db.commit()

print("=== VERIFY ===")
cur.execute(
    "SELECT category, COUNT(*) FROM email_subjects WHERE active=1 GROUP BY category"
)
for row in cur.fetchall():
    print(row)
cur.execute(
    "SELECT id, active, category, subject_line FROM email_subjects "
    "WHERE category IN ('modo_hogar','modo_viaje') ORDER BY category, id"
)
for row in cur.fetchall():
    print(row)

cur.close()
print("DONE")
