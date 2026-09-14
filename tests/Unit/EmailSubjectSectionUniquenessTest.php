<?php
/**
 * Unit: unicidad de asuntos entre secciones (sin BD).
 */
use PHPUnit\Framework\TestCase;
use Gac\Repositories\EmailSubjectRepository;

class EmailSubjectSectionUniquenessTest extends TestCase
{
    private EmailSubjectRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new EmailSubjectRepository();
    }

    public function testSectionKeyGroupsEspeciales(): void
    {
        $this->assertSame('general', $this->repo->sectionKey('general'));
        $this->assertSame('modo_hogar', $this->repo->sectionKey('modo_hogar'));
        $this->assertSame('modo_viaje', $this->repo->sectionKey('modo_viaje'));
        $this->assertSame('especiales', $this->repo->sectionKey('especial_leer'));
        $this->assertSame('especiales', $this->repo->sectionKey('especial_no_leer'));
    }

    public function testNormalizeSubjectLine(): void
    {
        $this->assertSame(
            'tu codigo de netflix',
            $this->repo->normalizeSubjectLine("  Tu   codigo   de   Netflix  ")
        );
    }

    public function testSectionLabels(): void
    {
        $this->assertSame('Generales', $this->repo->sectionLabel('general'));
        $this->assertSame('Código Temporal', $this->repo->sectionLabel('modo_hogar'));
        $this->assertSame('Actualizar Hogar', $this->repo->sectionLabel('modo_viaje'));
        $this->assertSame('Asuntos especiales', $this->repo->sectionLabel('especial_leer'));
    }
}
