"""
GAC - Servicio de Filtrado de Emails para Python
"""

import logging
from cron.repositories import SettingsRepository

logger = logging.getLogger(__name__)


class EmailFilterService:
    """Servicio para filtrar emails por asunto y/o por remitente (DE: Disney+, etc.)"""
    
    # Mapeo DE (remitente) -> plataforma para cuando el asunto no coincida
    SENDER_TO_PLATFORM = {
        'disney': 'disney',
        'disney+': 'disney',
        'disneyplus': 'disney',
        'netflix': 'netflix',
        'amazon': 'prime',
        'prime video': 'prime',
        'spotify': 'spotify',
        'crunchyroll': 'crunchyroll',
        'paramount': 'paramount',
        'paramount+': 'paramount',
        'chatgpt': 'chatgpt',
        'openai': 'chatgpt',
        'canva': 'canva',
    }
    
    def __init__(self):
        self.subject_patterns_cache = {}
        self._load_subject_patterns()
    
    def _platform_from_sender(self, from_name, from_email):
        """Obtener plataforma desde DE (remitente): nombre o dominio del correo."""
        if not from_name and not from_email:
            return None
        combined = ' '.join([
            (from_name or '').lower(),
            (from_email or '').lower()
        ])
        for key, platform in self.SENDER_TO_PLATFORM.items():
            if key in combined:
                return platform
        return None
    
    def _load_subject_patterns(self):
        """Cargar patrones de asuntos desde settings"""
        platforms = ['netflix', 'disney', 'prime', 'spotify', 'crunchyroll', 'paramount', 'chatgpt', 'canva']
        
        for platform in platforms:
            if SettingsRepository.is_platform_enabled(platform):
                subjects = SettingsRepository.get_email_subjects_for_platform(platform)
                if subjects:
                    self.subject_patterns_cache[platform] = subjects
    
    def filter_by_subject(self, emails):
        """Filtrar emails por asunto o por remitente (DE: Disney+, Netflix, etc.)"""
        filtered = []
        
        for email in emails:
            subject = email.get('subject', '')
            from_name = email.get('from_name', '')
            from_email = email.get('from', '')
            
            # 1) Plataforma desde asunto (tabla email_subjects o patrones)
            platform = self.match_subject_to_platform(subject) if subject else None
            
            # 2) Si no hay match por asunto, usar DE (remitente): Disney+, Netflix, etc.
            if not platform:
                platform = self._platform_from_sender(from_name, from_email)
            
            if platform:
                email['matched_platform'] = platform
                email['matched_subject'] = self.find_matching_subject(subject, platform) if subject else subject
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