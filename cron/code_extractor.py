"""
GAC - Servicio de Extracción de Códigos para Python
"""

import re
import logging

logger = logging.getLogger(__name__)


class CodeExtractorService:
    """Servicio para extraer códigos de emails"""
    
    def __init__(self):
        # Patrones regex por plataforma
        self.code_patterns = {
            'netflix': [
                r'\b(\d{6})\b',
                r'\b(\d{3}\s?\d{3})\b',
                r'código[:\s]+(\d{6})',
                r'code[:\s]+(\d{6})',
            ],
            'disney': [
                r'\b(\d{6,8})\b',
                r'\b(\d{3,4}\s?\d{3,4})\b',
                r'código[:\s]+(\d{6,8})',
                r'code[:\s]+(\d{6,8})',
            ],
            'prime': [
                r'\b(\d{6})\b',
                r'OTP[:\s]+(\d{6})',
                r'verification code[:\s]+(\d{6})',
                r'code[:\s]+(\d{6})',
            ],
            'spotify': [
                r'\b(\d{6})\b',
                r'verification code[:\s]+(\d{6})',
                r'código[:\s]+(\d{6})',
            ],
            'crunchyroll': [
                r'\b(\d{6})\b',
                r'código de acceso[:\s]+(\d{6})',
                r'access code[:\s]+(\d{6})',
            ],
            'paramount': [
                r'\b(\d{6})\b',
                r'código[:\s]+(\d{6})',
                r'code[:\s]+(\d{6})',
            ],
            'chatgpt': [
                r'\b(\d{6})\b',
                r'verification code[:\s]+(\d{6})',
                r'código[:\s]+(\d{6})',
            ],
            'canva': [
                r'\b(\d{6})\b',
                r'verification code[:\s]+(\d{6})',
                r'código[:\s]+(\d{6})',
            ],
        }
        
        # Identificadores de plataforma desde remitente (DE: Disney+, etc.)
        self.sender_identifiers = {
            'disney': ['disney', 'disney+', 'disneyplus'],
            'netflix': ['netflix'],
            'prime': ['amazon', 'prime'],
            'spotify': ['spotify'],
            'crunchyroll': ['crunchyroll'],
            'paramount': ['paramount'],
            'chatgpt': ['chatgpt', 'openai'],
            'canva': ['canva'],
        }
        
        # Identificadores de plataforma desde asunto
        self.platform_identifiers = {
            'netflix': [
                r'netflix',
                r'código de acceso temporal',
                r'código de inicio de sesión',
            ],
            'disney': [
                r'disney\+?',
                r'disney plus',
                r'código de acceso único',
            ],
            'prime': [
                r'amazon',
                r'prime video',
                r'sign-in attempt',
            ],
            'spotify': [r'spotify'],
            'crunchyroll': [r'crunchyroll'],
            'paramount': [r'paramount'],
            'chatgpt': [r'chatgpt', r'openai'],
            'canva': [r'canva'],
        }
    
    def identify_platform(self, subject):
        """Identificar plataforma desde asunto"""
        if not subject:
            return None
        
        subject_lower = subject.lower()
        
        for platform, patterns in self.platform_identifiers.items():
            for pattern in patterns:
                if re.search(pattern, subject_lower, re.IGNORECASE):
                    return platform
        
        return None
    
    def identify_platform_from_sender(self, from_name, from_email):
        """Identificar plataforma desde DE (remitente): Disney+, Netflix, etc."""
        combined = ' '.join([(from_name or '').lower(), (from_email or '').lower()])
        if not combined.strip():
            return None
        for platform, keywords in self.sender_identifiers.items():
            for kw in keywords:
                if kw in combined:
                    return platform
        return None
    
    def extract_code(self, email, platform=None):
        """Extraer código de un email"""
        # Identificar plataforma si no se proporciona: asunto o DE (remitente)
        if not platform:
            platform = self.identify_platform(email.get('subject', ''))
            if not platform:
                platform = self.identify_platform_from_sender(
                    email.get('from_name', ''),
                    email.get('from', '')
                )
            if not platform:
                return None
        
        # Verificar que la plataforma existe
        if platform not in self.code_patterns:
            return None
        
        # Obtener texto para buscar
        text = email.get('body_text', '')
        if not text and email.get('body_html'):
            # Extraer texto del HTML
            import html
            text = html.unescape(re.sub(r'<[^>]+>', '', email['body_html']))
        
        if not text:
            text = email.get('body', '')
        
        if not text:
            return None
        
        # Intentar extraer código con cada patrón
        patterns = self.code_patterns[platform]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                code = self._clean_code(match.group(1))
                
                # Validar código
                if self._validate_code(code, platform):
                    return {
                        'code': code,
                        'platform': platform,
                        'subject': email.get('subject', ''),
                        'from': email.get('from', ''),
                        'to': email.get('to', []),  # Lista de destinatarios
                        'to_primary': email.get('to_primary', ''),  # Destinatario principal
                        'date': email.get('date', ''),
                        'timestamp': email.get('timestamp', 0),
                        'extracted_at': email.get('date', ''),
                        # Incluir cuerpo para que se guarde en BD y se muestre en la consulta
                        'body': email.get('body', ''),
                        'body_text': email.get('body_text', ''),
                        'body_html': email.get('body_html', ''),
                    }
        
        return None
    
    def extract_codes(self, emails):
        """Extraer códigos de múltiples emails"""
        codes = []
        
        for email in emails:
            code = self.extract_code(email)
            if code:
                codes.append(code)
        
        return codes
    
    def _clean_code(self, code):
        """Limpiar código (remover espacios, guiones, etc.)"""
        return re.sub(r'[^\d]', '', code)
    
    def _validate_code(self, code, platform):
        """Validar código"""
        if not code.isdigit():
            return False
        
        # Longitudes por plataforma
        lengths = {
            'netflix': (6, 6),
            'disney': (6, 8),
            'prime': (6, 6),
            'spotify': (6, 6),
            'crunchyroll': (6, 6),
            'paramount': (6, 6),
            'chatgpt': (6, 6),
            'canva': (6, 6),
        }
        
        if platform not in lengths:
            return 6 <= len(code) <= 8
        
        min_len, max_len = lengths[platform]
        return min_len <= len(code) <= max_len