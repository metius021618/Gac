"""
Tests unitarios: clasificación de asuntos especiales (asunto + cuerpo).

Ejecutar: python -m unittest cron.tests.test_asuntos_especiales_classify -v
"""

import sys
import types
import unittest
from unittest.mock import MagicMock

# Drivers MySQL (pueden no estar en local)
sys.modules.setdefault("pymysql", MagicMock())
sys.modules.setdefault("pymysql.cursors", MagicMock())
sys.modules.setdefault("mysql", MagicMock())
sys.modules.setdefault("mysql.connector", MagicMock())

NETFLIX_SUBJECT = "Actualización importante de la cuenta"


class FakeRepo:
    @staticmethod
    def get_all_subjects_by_platform():
        return {"netflix": [NETFLIX_SUBJECT, "Tu código de acceso temporal de Netflix"]}

    @staticmethod
    def get_special_rules():
        return [
            {
                "id": 1,
                "subject_line": NETFLIX_SUBJECT,
                "body_match": "finalizar una compra en tu cuenta",
                "special_action": "no_leer",
                "platform_name": "netflix",
                "platform_id": 1,
            },
            {
                "id": 2,
                "subject_line": NETFLIX_SUBJECT,
                "body_match": "Recibimos una solicitud para cambiar la información de tu cuenta",
                "special_action": "leer",
                "platform_name": "netflix",
                "platform_id": 1,
            },
        ]


# Mock de capa BD/repos antes de importar email_filter
_fake_db = types.ModuleType("cron.database")
_fake_db.Database = MagicMock()
_fake_db.USE_PYMYSQL = True
sys.modules["cron.database"] = _fake_db

_fake_repos = types.ModuleType("cron.repositories")
_fake_repos.EmailSubjectRepository = FakeRepo
sys.modules["cron.repositories"] = _fake_repos

# Forzar reimport limpio
for name in list(sys.modules):
    if name.startswith("cron.email_filter"):
        del sys.modules[name]

from cron.email_filter import EmailFilterService  # noqa: E402


class TestAsuntosEspecialesClassify(unittest.TestCase):
    def setUp(self):
        self.svc = EmailFilterService()

    def test_purchase_is_discarded(self):
        email = {
            "subject": NETFLIX_SUBJECT,
            "matched_platform": "netflix",
            "body_html": "<p>Hola,</p><p>Estás a punto de finalizar una compra en tu cuenta de Netflix.</p>",
        }
        decision = self.svc.classify_email(email)
        self.assertEqual(decision["action"], EmailFilterService.ACTION_DISCARD)

    def test_account_change_is_save_special(self):
        email = {
            "subject": NETFLIX_SUBJECT,
            "matched_platform": "netflix",
            "body_text": "Recibimos una solicitud para cambiar la información de tu cuenta. Si no fuiste tú, cierra sesión.",
        }
        decision = self.svc.classify_email(email)
        self.assertEqual(decision["action"], EmailFilterService.ACTION_SAVE_SPECIAL)

    def test_normal_otp_is_save(self):
        email = {
            "subject": "Tu código de acceso temporal de Netflix",
            "matched_platform": "netflix",
            "body_html": "<p>Tu código es 123456</p>",
        }
        decision = self.svc.classify_email(email)
        self.assertEqual(decision["action"], EmailFilterService.ACTION_SAVE)

    def test_same_subject_without_body_match_is_save(self):
        email = {
            "subject": NETFLIX_SUBJECT,
            "matched_platform": "netflix",
            "body_html": "<p>Otro contenido distinto que no coincide con reglas especiales.</p>",
        }
        decision = self.svc.classify_email(email)
        self.assertEqual(decision["action"], EmailFilterService.ACTION_SAVE)

    def test_filter_includes_special_subject(self):
        emails = [{"subject": NETFLIX_SUBJECT, "body": "x"}]
        filtered = self.svc.filter_by_subject(emails)
        self.assertEqual(len(filtered), 1)
        self.assertEqual(filtered[0]["matched_platform"], "netflix")


if __name__ == "__main__":
    unittest.main()
