#!/usr/bin/env python3
import os, sys
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.chdir(ROOT)
from cron.database import Database

db = Database.get_connection()
cur = db.cursor()

cur.execute("SHOW TABLES LIKE 'email_subject_viewers'")
print("viewers_table", cur.fetchone())

cur.execute(
    "SELECT COUNT(*) FROM user_access WHERE enabled=1 "
    "AND password IS NOT NULL AND TRIM(password)<>'' "
    "AND password NOT IN ('Gmail (OAuth)','Outlook (OAuth)')"
)
print("eligible_users", cur.fetchone())

cur.execute(
    "SELECT id, category, subject_line FROM email_subjects "
    "WHERE active=1 AND category='especial_leer' ORDER BY id DESC LIMIT 5"
)
rows = cur.fetchall()
print("especial_leer", rows)
sid = rows[0][0] if rows else None
print("sid", sid)

if sid:
    try:
        cur.execute(
            """
            SELECT ua.password AS username,
                   MAX(CASE WHEN v.id IS NOT NULL THEN 1 ELSE 0 END) AS can_view,
                   COUNT(DISTINCT ua.id) AS accounts
            FROM user_access ua
            LEFT JOIN email_subject_viewers v
                   ON v.email_subject_id = %s
                  AND v.username = ua.password
            WHERE ua.enabled = 1
              AND ua.password IS NOT NULL
              AND TRIM(ua.password) <> ''
              AND ua.password NOT IN ('Gmail (OAuth)', 'Outlook (OAuth)')
            GROUP BY ua.password
            ORDER BY ua.password ASC
            LIMIT 10
            """,
            (sid,),
        )
        print("query_ok", cur.fetchall())
    except Exception as e:
        print("query_fail", e)

cur.close()
