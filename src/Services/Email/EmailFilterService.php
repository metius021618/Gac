<?php
/**
 * GAC - Servicio de Filtrado de Emails
 *
 * Filtra emails por asunto usando la tabla email_subjects.
 * Solo coincidencia EXACTA: el asunto del correo debe ser idéntico al registrado.
 *
 * @package Gac\Services\Email
 */

namespace Gac\Services\Email;

use Gac\Repositories\EmailSubjectRepository;

class EmailFilterService
{
    /**
     * Cache de asuntos por plataforma (desde email_subjects)
     */
    private array $subjectPatternsCache = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadSubjectPatterns();
    }

    /**
     * Cargar asuntos desde tabla email_subjects (solo plataformas habilitadas)
     */
    private function loadSubjectPatterns(): void
    {
        $this->subjectPatternsCache = EmailSubjectRepository::getAllSubjectsByPlatform();
    }

    /**
     * Filtrar emails por asunto
     * 
     * @param array $emails Array de emails
     * @return array Array de emails filtrados con información de plataforma
     */
    public function filterBySubject(array $emails): array
    {
        $filtered = [];
        
        foreach ($emails as $email) {
            $subject = $email['subject'] ?? '';
            
            if (empty($subject)) {
                continue;
            }
            
            // Intentar identificar plataforma y verificar si coincide con algún asunto
            $platform = $this->matchSubjectToPlatform($subject);
            
            if ($platform !== null) {
                $email['matched_platform'] = $platform;
                $email['matched_subject'] = $this->findMatchingSubject($subject, $platform);
                $filtered[] = $email;
            }
        }
        
        return $filtered;
    }

    /**
     * Verificar si un email coincide con algún asunto de una plataforma específica
     * 
     * @param array $email Datos del email
     * @param string $platform Slug de la plataforma
     * @return bool
     */
    public function matchesPlatform(array $email, string $platform): bool
    {
        $subject = $email['subject'] ?? '';
        
        if (empty($subject)) {
            return false;
        }
        
        if (!isset($this->subjectPatternsCache[$platform])) {
            return false;
        }
        
        $subjects = $this->subjectPatternsCache[$platform];
        
        return $this->matchesAnySubject($subject, $subjects);
    }

    /**
     * Identificar plataforma desde asunto
     * 
     * @param string $subject
     * @return string|null Slug de la plataforma o null
     */
    public function matchSubjectToPlatform(string $subject): ?string
    {
        foreach ($this->subjectPatternsCache as $platform => $subjects) {
            if ($this->matchesAnySubject($subject, $subjects)) {
                return $platform;
            }
        }
        
        return null;
    }

    /**
     * Encontrar el asunto que coincide
     * 
     * @param string $subject
     * @param string $platform
     * @return string|null
     */
    public function findMatchingSubject(string $subject, string $platform): ?string
    {
        if (!isset($this->subjectPatternsCache[$platform])) {
            return null;
        }
        
        $subjects = $this->subjectPatternsCache[$platform];
        
        foreach ($subjects as $pattern) {
            if ($this->matchesSubject($subject, $pattern)) {
                return $pattern;
            }
        }
        
        return null;
    }

    /**
     * Verificar si un asunto coincide con alguno de los patrones
     * 
     * @param string $subject
     * @param array $patterns
     * @return bool
     */
    private function matchesAnySubject(string $subject, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->matchesSubject($subject, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verificar si un asunto coincide EXACTAMENTE con un patrón.
     * Solo igualdad exacta (tras normalizar espacios y mayúsculas).
     * No se usa contains ni similitud: "TU CODIG" != "TU CODIGO CORREO ES".
     *
     * @param string $subject Asunto del correo recibido
     * @param string $pattern Asunto registrado en email_subjects
     * @return bool
     */
    private function matchesSubject(string $subject, string $pattern): bool
    {
        $subject = $this->normalizeSubject($subject);
        $pattern = $this->normalizeSubject($pattern);
        return $subject === $pattern;
    }

    /**
     * Normalizar asunto: trim, colapsar espacios múltiples, NFC unicode.
     */
    private function normalizeSubject(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        if (class_exists('Normalizer')) {
            $n = \Normalizer::normalize($s, \Normalizer::FORM_C);
            $s = $n !== false ? $n : $s;
        }
        return mb_strtolower($s, 'UTF-8');
    }

    /**
     * Filtrar emails por plataforma específica
     * 
     * @param array $emails
     * @param string $platform
     * @return array
     */
    public function filterByPlatform(array $emails, string $platform): array
    {
        $filtered = [];
        
        foreach ($emails as $email) {
            if ($this->matchesPlatform($email, $platform)) {
                $email['matched_platform'] = $platform;
                $email['matched_subject'] = $this->findMatchingSubject($email['subject'] ?? '', $platform);
                $filtered[] = $email;
            }
        }
        
        return $filtered;
    }

    /**
     * Obtener estadísticas de filtrado
     * 
     * @param array $emails
     * @return array
     */
    public function getFilteringStats(array $emails): array
    {
        $stats = [
            'total' => count($emails),
            'filtered' => 0,
            'by_platform' => []
        ];
        
        $filtered = $this->filterBySubject($emails);
        $stats['filtered'] = count($filtered);
        
        foreach ($filtered as $email) {
            $platform = $email['matched_platform'] ?? 'unknown';
            if (!isset($stats['by_platform'][$platform])) {
                $stats['by_platform'][$platform] = 0;
            }
            $stats['by_platform'][$platform]++;
        }
        
        return $stats;
    }

    /**
     * Recargar patrones desde email_subjects (útil cuando se actualizan asuntos)
     */
    public function reloadPatterns(): void
    {
        $this->subjectPatternsCache = [];
        $this->loadSubjectPatterns();
    }
}