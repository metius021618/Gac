#!/usr/bin/env python3
"""Migración: permisos de especiales por asunto×usuario + special_subject_id en codes."""
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


def table_exists(name):
    cur.execute("SHOW TABLES LIKE %s", (name,))
    return cur.fetchone() is not None


def index_exists(table, name):
    cur.execute(f"SHOW INDEX FROM {table} WHERE Key_name=%s", (name,))
    return cur.fetchone() is not None


try:
    if not table_exists("email_subject_viewers"):
        cur.execute(
            """
            CREATE TABLE email_subject_viewers (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                email_subject_id INT UNSIGNED NOT NULL,
                username VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_subject_username (email_subject_id, username),
                KEY idx_esv_username (username),
                KEY idx_esv_subject (email_subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci
            """
        )
        print("CREATE email_subject_viewers")
    else:
        try:
            cur.execute(
                "ALTER TABLE email_subject_viewers "
                "CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci"
            )
            print("ALIGN collations email_subject_viewers -> utf8mb4_spanish_ci")
        except Exception as e:
            print("COLLATION align skip:", e)
    if not col_exists("codes", "special_subject_id"):
        cur.execute(
            "ALTER TABLE codes "
            "ADD COLUMN special_subject_id INT UNSIGNED NULL "
            "COMMENT 'email_subjects.id si is_special=1' AFTER is_special"
        )
        print("ADD codes.special_subject_id")
    if not index_exists("codes", "idx_codes_special_subject_id"):
        try:
            cur.execute(
                "ALTER TABLE codes ADD INDEX idx_codes_special_subject_id (special_subject_id)"
            )
            print("ADD INDEX idx_codes_special_subject_id")
        except Exception as e:
            print("INDEX skip:", e)

    db.commit()
    print("MIGRATION_OK")
except Exception as e:
    db.rollback()
    print("MIGRATION_FAIL:", e)
    sys.exit(1)
finally:
    cur.close()
