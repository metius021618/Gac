"""
GAC - Servicio de Filtrado de Emails para Python
Filtra correos SOLO por los asuntos definidos en la página de asuntos (tabla email_subjects).
Usado por Gmail, Outlook e IMAP (Pocoyoni) de forma consistente.
"""

import logging
from cron.repositories import EmailSubjectRepository

logger = logging.getLogger(__name__)


class EmailFilterService:
    """Servicio para filtrar emails únicamente por asunto (tabla email_subjects)."""

    def __init__(self):
        self.subject_patterns_cache = {}
        self._load_subject_patterns()

    def _load_subject_patterns(self):
        """Cargar asuntos desde tabla email_subjects (misma fuente que la página de asuntos)."""
        self.subject_patterns_cache = EmailSubjectRepository.get_all_subjects_by_platform()
        if not self.subject_patterns_cache:
            logger.warning("No hay asuntos activos en email_subjects; ningún correo será filtrado por asunto.")

    def filter_by_subject(self, emails):
        """Filtrar emails SOLO por asunto: solo pasan los que coinciden con un asunto de email_subjects."""
        filtered = []

        for email in emails:
            subject = email.get('subject', '')
            if not subject:
                continue
            platform = self.match_subject_to_platform(subject)
            if platform:
                email['matched_platform'] = platform
                email['matched_subject'] = self.find_matching_subject(subject, platform) or subject
                filtered.append(email)

        return filtered
    
    def match_subject_to_platform(self, subject):
        """Identificar plataforma desde asunto"""
        subject_lower = subject.lower()
        
        for platform, subjects in self.subject_patterns_cache.items():
            for pattern in subjects:
                if self._matches_subject(subject_lower, pattern.lower()):
                    return platform
        
        return None
    
    def find_matching_subject(self, subject, platform):
        """Encontrar asunto guardado que coincide exactamente con el del correo."""
        if platform not in self.subject_patterns_cache:
            return None
        subject_norm = (subject or '').strip()
        for pattern in self.subject_patterns_cache[platform]:
            if self._matches_subject_exact(subject_norm, (pattern or '').strip()):
                return pattern
        return None

    def _matches_subject(self, subject, pattern):
        """Comparación exacta: el asunto del correo debe ser igual a uno de los guardados en la vista de asuntos."""
        return self._matches_subject_exact((subject or '').strip(), (pattern or '').strip())

    def _matches_subject_exact(self, subject, pattern):
        """Igualdad exacta (ignorando mayúsculas/minúsculas) para no leer ni guardar otro correo."""
        if not subject or not pattern:
            return False
        return subject.lower() == pattern.lower()