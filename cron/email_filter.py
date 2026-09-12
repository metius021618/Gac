"""
GAC - Filtrado de emails por asunto (email_subjects) + asuntos especiales (asunto + cuerpo).

Categorías normales: general | modo_hogar | modo_viaje → match exacto de asunto.
Asuntos especiales:
  - especial_leer: asunto exacto + cuerpo (normalizado) coincide → guardar con is_special=1
  - especial_no_leer: asunto exacto + cuerpo coincide → descartar (no guardar)
"""

import re
import unicodedata
import logging
from html import unescape
from cron.repositories import EmailSubjectRepository

logger = logging.getLogger(__name__)


class EmailFilterService:
    """Filtra emails por asunto y clasifica especiales por cuerpo."""

    ACTION_SAVE = "save"
    ACTION_SAVE_SPECIAL = "save_special"
    ACTION_DISCARD = "discard"

    def __init__(self):
        self.subject_patterns_cache = {}
        self.special_rules = []  # list of dicts
        self._load_subject_patterns()

    def _load_subject_patterns(self):
        self.subject_patterns_cache = EmailSubjectRepository.get_all_subjects_by_platform()
        self.special_rules = EmailSubjectRepository.get_special_rules()
        if not self.subject_patterns_cache and not self.special_rules:
            logger.warning("No hay asuntos activos en email_subjects.")

    def filter_by_subject(self, emails):
        """Pasan correos cuyo asunto coincide con algún asunto activo (incl. especiales)."""
        filtered = []
        for email in emails:
            subject = email.get("subject", "")
            if not subject:
                continue
            platform = self.match_subject_to_platform(subject)
            if platform:
                email["matched_platform"] = platform
                email["matched_subject"] = self.find_matching_subject(subject, platform) or subject
                filtered.append(email)
        return filtered

    def classify_email(self, email):
        """
        Tras tener cuerpo disponible, decide acción.
        Returns: action (save|save_special|discard), platform, matched_subject, special_rule|None
        """
        subject = email.get("subject", "") or ""
        body = (
            email.get("body_html")
            or email.get("body_text")
            or email.get("body")
            or email.get("email_body")
            or ""
        )
        platform = email.get("matched_platform") or self.match_subject_to_platform(subject)
        if not platform:
            return {
                "action": None,
                "platform": None,
                "matched_subject": None,
                "special_rule": None,
            }

        # Primero descartes (no_leer), luego leer especial, luego normal
        for rule in self.special_rules:
            if rule.get("special_action") != "no_leer":
                continue
            if (rule.get("platform_name") or "").lower() != (platform or "").lower():
                continue
            if not self._matches_subject_exact(subject, rule.get("subject_line") or ""):
                continue
            if self._body_matches(body, rule.get("body_match") or ""):
                return {
                    "action": self.ACTION_DISCARD,
                    "platform": platform,
                    "matched_subject": rule.get("subject_line"),
                    "special_rule": rule,
                }

        for rule in self.special_rules:
            if rule.get("special_action") != "leer":
                continue
            if (rule.get("platform_name") or "").lower() != (platform or "").lower():
                continue
            if not self._matches_subject_exact(subject, rule.get("subject_line") or ""):
                continue
            if self._body_matches(body, rule.get("body_match") or ""):
                return {
                    "action": self.ACTION_SAVE_SPECIAL,
                    "platform": platform,
                    "matched_subject": rule.get("subject_line"),
                    "special_rule": rule,
                }

        return {
            "action": self.ACTION_SAVE,
            "platform": platform,
            "matched_subject": self.find_matching_subject(subject, platform) or subject,
            "special_rule": None,
        }

    def match_subject_to_platform(self, subject):
        subject_lower = (subject or "").lower()
        # Incluir asuntos especiales en el caché de plataformas
        for platform, subjects in self.subject_patterns_cache.items():
            for pattern in subjects:
                if self._matches_subject(subject_lower, pattern.lower()):
                    return platform
        for rule in self.special_rules:
            if self._matches_subject_exact(subject, rule.get("subject_line") or ""):
                return (rule.get("platform_name") or "").lower() or None
        return None

    def find_matching_subject(self, subject, platform):
        if platform not in self.subject_patterns_cache:
            return None
        subject_norm = (subject or "").strip()
        for pattern in self.subject_patterns_cache[platform]:
            if self._matches_subject_exact(subject_norm, (pattern or "").strip()):
                return pattern
        return None

    def _matches_subject(self, subject, pattern):
        return self._matches_subject_exact((subject or "").strip(), (pattern or "").strip())

    @staticmethod
    def _normalize_subject(s):
        if not s or not isinstance(s, str):
            return ""
        s = (s or "").strip()
        s = re.sub(r"\s+", " ", s)
        try:
            s = unicodedata.normalize("NFC", s)
        except Exception:
            pass
        return s

    def _matches_subject_exact(self, subject, pattern):
        if not subject or not pattern:
            return False
        a = self._normalize_subject(subject).lower()
        b = self._normalize_subject(pattern).lower()
        return a == b

    @staticmethod
    def normalize_body_text(raw):
        """HTML/texto → texto plano normalizado para comparar cuerpos."""
        if not raw or not isinstance(raw, str):
            return ""
        text = unescape(raw)
        text = re.sub(r"(?is)<script[^>]*>.*?</script>", " ", text)
        text = re.sub(r"(?is)<style[^>]*>.*?</style>", " ", text)
        text = re.sub(r"(?is)<br\s*/?>", "\n", text)
        text = re.sub(r"(?is)</p>", "\n", text)
        text = re.sub(r"(?is)<[^>]+>", " ", text)
        text = re.sub(r"&nbsp;", " ", text)
        text = re.sub(r"\s+", " ", text).strip()
        try:
            text = unicodedata.normalize("NFC", text)
        except Exception:
            pass
        return text.lower()

    def _body_matches(self, email_body, body_match):
        needle = self.normalize_body_text(body_match)
        haystack = self.normalize_body_text(email_body)
        if not needle or not haystack:
            return False
        # Exacto o contenido (el correo suele traer footer/extra)
        return haystack == needle or needle in haystack
