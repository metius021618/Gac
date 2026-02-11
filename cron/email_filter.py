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
        """Encontrar asunto que coincide"""
        if platform not in self.subject_patterns_cache:
            return None
        
        subject_lower = subject.lower()
        subjects = self.subject_patterns_cache[platform]
        
        for pattern in subjects:
            if self._matches_subject(subject_lower, pattern.lower()):
                return pattern
        
        return None
    
    def _matches_subject(self, subject, pattern):
        """Verificar si asunto coincide con patrón"""
        # Comparación exacta
        if subject == pattern:
            return True
        
        # Contains
        if pattern in subject or subject in pattern:
            return True
        
        # Similitud (simplificado)
        similarity = self._calculate_similarity(subject, pattern)
        if similarity >= 0.8:
            return True
        
        return False
    
    def _calculate_similarity(self, str1, str2):
        """Calcular similitud entre strings"""
        if str1 == str2:
            return 1.0
        
        if not str1 or not str2:
            return 0.0
        
        # Distancia de Levenshtein simplificada
        max_len = max(len(str1), len(str2))
        distance = self._levenshtein_distance(str1, str2)
        
        similarity = 1 - (distance / max_len)
        return max(0.0, min(1.0, similarity))
    
    def _levenshtein_distance(self, s1, s2):
        """Calcular distancia de Levenshtein"""
        if len(s1) < len(s2):
            return self._levenshtein_distance(s2, s1)
        
        if len(s2) == 0:
            return len(s1)
        
        previous_row = range(len(s2) + 1)
        for i, c1 in enumerate(s1):
            current_row = [i + 1]
            for j, c2 in enumerate(s2):
                insertions = previous_row[j + 1] + 1
                deletions = current_row[j] + 1
                substitutions = previous_row[j] + (c1 != c2)
                current_row.append(min(insertions, deletions, substitutions))
            previous_row = current_row
        
        return previous_row[-1]