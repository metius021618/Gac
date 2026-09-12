#!/usr/bin/env python3
"""Migración one-shot: asuntos especiales + is_special + can_view_special."""
import os
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.chdir(ROOT)

from cron.database import Database

db = Database.get_connection()
cur = db.cursor()


def col_exists(table, col):
    cur.execute(f"SHOW COLUMNS FROM {table} LIKE %s", (col,))
    return cur.fetchone() is not None


def index_exists(table, name):
    cur.execute(f"SHOW INDEX FROM {table} WHERE Key_name=%s", (name,))
    return cur.fetchone() is not None


try:
    if not col_exists("email_subjects", "body_match"):
        cur.execute(
            "ALTER TABLE email_subjects "
            "ADD COLUMN body_match TEXT NULL COMMENT 'Cuerpo para asuntos especiales' AFTER category"
        )
        print("ADD email_subjects.body_match")
    if not col_exists("email_subjects", "special_action"):
        cur.execute(
            "ALTER TABLE email_subjects "
            "ADD COLUMN special_action VARCHAR(16) NULL COMMENT 'leer|no_leer' AFTER body_match"
        )
        print("ADD email_subjects.special_action")

    if not col_exists("codes", "is_special"):
        cur.execute(
            "ALTER TABLE codes "
            "ADD COLUMN is_special TINYINT(1) NOT NULL DEFAULT 0 "
            "COMMENT '1=asunto especial' AFTER status"
        )
        print("ADD codes.is_special")
    if not index_exists("codes", "idx_codes_is_special"):
        try:
            cur.execute("ALTER TABLE codes ADD INDEX idx_codes_is_special (is_special)")
            print("ADD INDEX idx_codes_is_special")
        except Exception as e:
            print("INDEX codes skip:", e)

    if not col_exists("user_access", "can_view_special"):
        cur.execute(
            "ALTER TABLE user_access "
            "ADD COLUMN can_view_special TINYINT(1) NOT NULL DEFAULT 0 "
            "COMMENT '1=puede ver especiales' AFTER enabled"
        )
        print("ADD user_access.can_view_special")
    if not index_exists("user_access", "idx_user_access_can_view_special"):
        try:
            cur.execute(
                "ALTER TABLE user_access ADD INDEX idx_user_access_can_view_special (can_view_special)"
            )
            print("ADD INDEX idx_user_access_can_view_special")
        except Exception as e:
            print("INDEX user_access skip:", e)

    if index_exists("email_subjects", "unique_platform_subject"):
        cur.execute("ALTER TABLE email_subjects DROP INDEX unique_platform_subject")
        print("DROP unique_platform_subject")

    db.commit()
    print("MIGRATION_OK")
except Exception as e:
    db.rollback()
    print("MIGRATION_FAIL:", e)
    sys.exit(1)
finally:
    cur.close()
