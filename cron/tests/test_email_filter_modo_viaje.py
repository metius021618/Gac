"""
Unit tests: el filtro de correos debe aceptar asuntos exactos (incl. Modo Viaje).
Ejecutar: python -m unittest cron.tests.test_email_filter_modo_viaje -v
"""

import unittest
import unicodedata
import re


def normalize_subject(s: str) -> str:
    s = (s or "").strip()
    s = re.sub(r"\s+", " ", s)
    s = unicodedata.normalize("NFC", s)
    return s.casefold()


def match_exact(email_subject: str, registered_lines) -> bool:
    needle = normalize_subject(email_subject)
    for line in registered_lines:
        if normalize_subject(line) == needle:
            return True
    return False


class TestModoViajeExactSubjectFilter(unittest.TestCase):
    def test_registered_modo_viaje_subject_is_read(self):
        registered = [
            "Tu código de acceso temporal de Netflix",
            "Estoy de viaje",  # típico asunto MODO VIAJE
        ]
        self.assertTrue(match_exact("Estoy de viaje", registered))
        self.assertTrue(match_exact("estoy de viaje", registered))

    def test_unregistered_or_partial_is_ignored(self):
        registered = ["Estoy de viaje"]
        self.assertFalse(match_exact("Estoy de viaje ahora", registered))
        self.assertFalse(match_exact("Importante: Cómo actualizar tu Hogar", registered))


if __name__ == "__main__":
    unittest.main()
