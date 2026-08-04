<?php
/**
 * Unit: coincidencia exacta de asuntos Modo Viaje (misma regla que el lector).
 */

namespace Gac\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ModoViajeExactSubjectMatchTest extends TestCase
{
    /**
     * Replica la regla de EmailFilterService: igualdad exacta tras normalizar
     * (trim + colapsar espacios + casefold). El asunto registrado tal cual debe matchear.
     */
    private function normalize(string $s): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize($s, \Normalizer::FORM_C);
            if (is_string($n)) {
                $s = $n;
            }
        }
        return mb_strtolower($s, 'UTF-8');
    }

    private function matchesExact(string $emailSubject, array $registered): bool
    {
        $needle = $this->normalize($emailSubject);
        foreach ($registered as $line) {
            if ($this->normalize((string) $line) === $needle) {
                return true;
            }
        }
        return false;
    }

    public function test_exact_registered_subject_matches(): void
    {
        $registered = ['Estoy de viaje', 'Actualiza tu hogar mientras viajas'];
        $this->assertTrue($this->matchesExact('Estoy de viaje', $registered));
        $this->assertTrue($this->matchesExact('ESTOY DE VIAJE', $registered));
    }

    public function test_partial_subject_does_not_match(): void
    {
        $registered = ['Estoy de viaje'];
        $this->assertFalse($this->matchesExact('Estoy de viaje - extra', $registered));
        $this->assertFalse($this->matchesExact('viaje', $registered));
    }
}
