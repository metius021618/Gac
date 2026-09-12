#!/usr/bin/env python3
"""
Validación end-to-end de asuntos especiales (sin Gmail real).

Simula el mismo camino del cron: filter_by_subject → classify_email → save/discard.
Inserta filas de prueba en `codes` y verifica consulta (opción A).

Uso:
  python3 scripts/validate_asuntos_especiales_e2e.py
  python3 scripts/validate_asuntos_especiales_e2e.py --keep   # no limpia filas de prueba
"""
from __future__ import annotations

import argparse
import os
import sys
from datetime import datetime, timedelta

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
os.chdir(ROOT)

from cron.database import Database
from cron.email_filter import EmailFilterService
from cron.repositories import CodeRepository, PlatformRepository

MARKER = "[GAC-TEST-ESPECIAL]"
SUBJECT_SPECIAL = "Actualización importante de la cuenta"
SUBJECT_OTP = "Tu código de acceso temporal de Netflix"
FROM_FAKE = "info@mailer.netflix.com"


def ensure_special_rules(cur, platform_id: int) -> None:
    rules = [
        (
            "especial_leer",
            "leer",
            "Recibimos una solicitud para cambiar la información de tu cuenta",
        ),
        (
            "especial_no_leer",
            "no_leer",
            "finalizar una compra en tu cuenta",
        ),
    ]
    for category, action, body in rules:
        cur.execute(
            """
            SELECT id FROM email_subjects
            WHERE platform_id=%s AND category=%s AND active=1
              AND subject_line=%s AND body_match=%s
            LIMIT 1
            """,
            (platform_id, category, SUBJECT_SPECIAL, body),
        )
        if cur.fetchone():
            continue
        cur.execute(
            """
            INSERT INTO email_subjects
              (platform_id, subject_line, category, body_match, special_action, active)
            VALUES (%s,%s,%s,%s,%s,1)
            """,
            (platform_id, SUBJECT_SPECIAL, category, body, action),
        )
        print(f"SEED rule {category} id={cur.lastrowid}")


def pick_recipient(cur, platform_id: int) -> str:
    cur.execute(
        """
        SELECT email FROM user_access
        WHERE platform_id=%s AND enabled=1 AND email LIKE %s
        ORDER BY id DESC LIMIT 1
        """,
        (platform_id, "%@gmail.com"),
    )
    row = cur.fetchone()
    if row:
        return (row[0] if not isinstance(row, dict) else row["email"]).strip().lower()
    cur.execute(
        """
        SELECT email FROM user_access
        WHERE platform_id=%s AND enabled=1
        ORDER BY id DESC LIMIT 1
        """,
        (platform_id,),
    )
    row = cur.fetchone()
    if not row:
        raise RuntimeError("No hay user_access para Netflix; no se puede validar consulta")
    return (row[0] if not isinstance(row, dict) else row["email"]).strip().lower()


def pick_email_account_id(cur) -> int:
    cur.execute(
        """
        SELECT id FROM email_accounts
        WHERE enabled=1 AND type='gmail'
        ORDER BY id ASC LIMIT 1
        """
    )
    row = cur.fetchone()
    if row:
        return int(row[0] if not isinstance(row, dict) else row["id"])
    cur.execute("SELECT id FROM email_accounts ORDER BY id ASC LIMIT 1")
    row = cur.fetchone()
    if not row:
        raise RuntimeError("No hay email_accounts")
    return int(row[0] if not isinstance(row, dict) else row["id"])


def process_like_cron(filter_svc, email_data, account_id, platform_obj):
    """Misma lógica esencial que email_reader_gmail (classify + save/discard)."""
    filtered = filter_svc.filter_by_subject([email_data])
    if not filtered:
        return {"action": None, "code_id": None, "reason": "no_subject_match"}
    email = filtered[0]
    decision = filter_svc.classify_email(email)
    action = decision.get("action")
    if action == EmailFilterService.ACTION_DISCARD:
        return {"action": action, "code_id": None, "reason": "discard"}
    save_data = {
        "email_account_id": account_id,
        "platform_id": platform_obj["id"],
        "code": email_data.get("from") or FROM_FAKE,
        "email_from": email_data.get("from") or FROM_FAKE,
        "subject": email_data.get("subject"),
        "email_body": email_data.get("body_html") or email_data.get("body_text") or "",
        "received_at": email_data.get("date"),
        "origin": "gmail",
        "recipient_email": email_data.get("to_primary"),
        "is_special": 1 if action == EmailFilterService.ACTION_SAVE_SPECIAL else 0,
    }
    code_id = CodeRepository.save(save_data)
    return {"action": action, "code_id": code_id, "reason": "saved", "is_special": save_data["is_special"]}


def set_can_view_special(cur, email: str, flag: int) -> None:
    cur.execute(
        "UPDATE user_access SET can_view_special=%s WHERE LOWER(TRIM(email))=LOWER(TRIM(%s))",
        (flag, email),
    )


def latest_code(cur, platform_id: int, recipient: str, exclude_special: bool = False):
    extra = " AND COALESCE(is_special,0)=0" if exclude_special else ""
    cur.execute(
        f"""
        SELECT id, subject, COALESCE(is_special,0) AS is_special, received_at
        FROM codes
        WHERE platform_id=%s AND LOWER(recipient_email)=LOWER(%s)
        {extra}
        ORDER BY received_at DESC, id DESC
        LIMIT 1
        """,
        (platform_id, recipient),
    )
    return cur.fetchone()


def cleanup(cur, recipient: str) -> int:
    cur.execute(
        """
        DELETE FROM codes
        WHERE LOWER(recipient_email)=LOWER(%s)
          AND (email_from=%s OR email_body LIKE %s OR subject LIKE %s)
        """,
        (recipient, FROM_FAKE, f"%{MARKER}%", f"%{MARKER}%"),
    )
    return cur.rowcount


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--keep", action="store_true", help="No borrar filas de prueba")
    args = parser.parse_args()

    db = Database.get_connection()
    cur = db.cursor()
    failed = 0

    platform = PlatformRepository.find_by_name("netflix")
    if not platform or not platform.get("enabled"):
        print("FAIL: plataforma netflix no disponible")
        return 1
    platform_id = int(platform["id"])

    ensure_special_rules(cur, platform_id)
    db.commit()

    recipient = pick_recipient(cur, platform_id)
    account_id = pick_email_account_id(cur)
    print(f"recipient={recipient}")
    print(f"email_account_id={account_id}")
    print(f"platform_id={platform_id}")

    # Limpiar restos previos de esta prueba
    n = cleanup(cur, recipient)
    db.commit()
    print(f"cleanup_prev={n}")

    # Asegurar asunto OTP (modo_hogar o general) para el caso normal
    cur.execute(
        """
        SELECT id FROM email_subjects
        WHERE platform_id=%s AND active=1 AND subject_line=%s
        LIMIT 1
        """,
        (platform_id, SUBJECT_OTP),
    )
    if not cur.fetchone():
        cur.execute(
            """
            INSERT INTO email_subjects (platform_id, subject_line, category, active)
            VALUES (%s, %s, 'modo_hogar', 1)
            """,
            (platform_id, SUBJECT_OTP),
        )
        print(f"SEED otp subject id={cur.lastrowid}")
        db.commit()

    filter_svc = EmailFilterService()
    print(f"special_rules_loaded={len(filter_svc.special_rules)}")
    if len(filter_svc.special_rules) < 2:
        print("FAIL: faltan reglas especiales en BD")
        return 1

    base = datetime.now()
    fixtures = [
        {
            "name": "compra_discard",
            "subject": SUBJECT_SPECIAL,
            "body": f"<p>{MARKER}</p><p>Estás a punto de finalizar una compra en tu cuenta de Netflix.</p>",
            "dt": (base - timedelta(minutes=3)).strftime("%Y-%m-%d %H:%M:%S"),
            "expect_action": EmailFilterService.ACTION_DISCARD,
            "expect_saved": False,
        },
        {
            "name": "otp_normal",
            "subject": SUBJECT_OTP,
            "body": f"<p>{MARKER}</p><p>Tu código de acceso temporal es 111222.</p>",
            "dt": (base - timedelta(minutes=2)).strftime("%Y-%m-%d %H:%M:%S"),
            "expect_action": EmailFilterService.ACTION_SAVE,
            "expect_saved": True,
            "expect_special": 0,
        },
        {
            "name": "cambio_cuenta_special",
            "subject": SUBJECT_SPECIAL,
            "body": (
                f"<p>{MARKER}</p><p>Recibimos una solicitud para cambiar la información "
                "de tu cuenta. Si no fuiste tú, cierra sesión en otros dispositivos.</p>"
            ),
            "dt": (base - timedelta(minutes=1)).strftime("%Y-%m-%d %H:%M:%S"),
            "expect_action": EmailFilterService.ACTION_SAVE_SPECIAL,
            "expect_saved": True,
            "expect_special": 1,
        },
    ]

    saved_ids = []
    for fx in fixtures:
        email = {
            "subject": fx["subject"],
            "from": FROM_FAKE,
            "to_primary": recipient,
            "date": fx["dt"],
            "body_html": fx["body"],
        }
        result = process_like_cron(filter_svc, email, account_id, platform)
        action = result.get("action")
        ok_action = action == fx["expect_action"]
        saved = result.get("code_id") is not None
        ok_saved = saved == fx["expect_saved"]
        ok_special = True
        if fx.get("expect_saved") and result.get("code_id"):
            saved_ids.append(result["code_id"])
            ok_special = int(result.get("is_special") or 0) == int(fx.get("expect_special", 0))
            # verificar en BD
            cur.execute(
                "SELECT COALESCE(is_special,0) FROM codes WHERE id=%s",
                (result["code_id"],),
            )
            row = cur.fetchone()
            db_flag = int(row[0] if not isinstance(row, dict) else list(row.values())[0])
            ok_special = ok_special and db_flag == int(fx.get("expect_special", 0))

        # Descarte: no debe existir fila con ese received_at
        if fx["expect_action"] == EmailFilterService.ACTION_DISCARD:
            cur.execute(
                """
                SELECT COUNT(*) FROM codes
                WHERE LOWER(recipient_email)=LOWER(%s) AND subject=%s AND received_at=%s
                """,
                (recipient, fx["subject"], fx["dt"]),
            )
            cnt = cur.fetchone()
            cnt = int(cnt[0] if not isinstance(cnt, dict) else list(cnt.values())[0])
            ok_saved = ok_saved and cnt == 0

        status = "OK" if (ok_action and ok_saved and ok_special) else "FAIL"
        if status == "FAIL":
            failed += 1
        print(
            f"[{status}] {fx['name']}: action={action} code_id={result.get('code_id')} "
            f"expect_action={fx['expect_action']} expect_saved={fx['expect_saved']}"
        )

    # Consulta opción A: último es especial; sin permiso → OTP; con permiso → especial
    set_can_view_special(cur, recipient, 0)
    db.commit()
    last_all = latest_code(cur, platform_id, recipient, exclude_special=False)
    last_normal = latest_code(cur, platform_id, recipient, exclude_special=True)
    if not last_all or not last_normal:
        print("FAIL: no hay códigos de prueba para validar consulta")
        failed += 1
    else:
        last_all_special = int(last_all[2] if not isinstance(last_all, dict) else last_all["is_special"])
        last_normal_special = int(
            last_normal[2] if not isinstance(last_normal, dict) else last_normal["is_special"]
        )
        # Sin permiso: debería usar last_normal (is_special=0)
        ok_gate_off = last_all_special == 1 and last_normal_special == 0
        print(
            f"[{'OK' if ok_gate_off else 'FAIL'}] consult_gate_off: "
            f"latest_is_special={last_all_special} fallback_is_special={last_normal_special} "
            f"fallback_id={last_normal[0] if not isinstance(last_normal, dict) else last_normal['id']}"
        )
        if not ok_gate_off:
            failed += 1

        set_can_view_special(cur, recipient, 1)
        db.commit()
        last_with = latest_code(cur, platform_id, recipient, exclude_special=False)
        last_with_special = int(
            last_with[2] if not isinstance(last_with, dict) else last_with["is_special"]
        )
        ok_gate_on = last_with_special == 1
        print(
            f"[{'OK' if ok_gate_on else 'FAIL'}] consult_gate_on: "
            f"latest_is_special={last_with_special} "
            f"id={last_with[0] if not isinstance(last_with, dict) else last_with['id']}"
        )
        if not ok_gate_on:
            failed += 1

        # Dejar permiso en 0 (default seguro) salvo --keep
        set_can_view_special(cur, recipient, 0)
        db.commit()

    if not args.keep:
        deleted = cleanup(cur, recipient)
        db.commit()
        print(f"cleanup_done={deleted}")
    else:
        print(f"KEEP test codes ids={saved_ids}")

    cur.close()
    if failed:
        print(f"RESULT=FAIL ({failed})")
        return 1
    print("RESULT=OK")
    return 0


if __name__ == "__main__":
    sys.exit(main())
