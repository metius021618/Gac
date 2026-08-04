<?php
/**
 * Tests unitarios: alta de asuntos MODO VIAJE vía payload del front
 * (mismo JSON que email_subjects.js → POST /admin/email-subjects).
 * No usa INSERT directo a la BD.
 */

namespace Gac\Tests\Integration;

use Gac\Controllers\EmailSubjectController;
use Gac\Repositories\EmailSubjectRepository;
use Gac\Repositories\PlatformRepository;
use PHPUnit\Framework\TestCase;

class ModoViajeSubjectCrudViaFrontTest extends TestCase
{
    private EmailSubjectController $controller;
    private EmailSubjectRepository $subjectRepo;
    private ?int $createdId = null;

    protected function setUp(): void
    {
        if (!getenv('DB_HOST') && empty($_ENV['DB_HOST'])) {
            $this->markTestSkipped('Sin DB_HOST: configurar .env para pruebas de integración');
        }
        try {
            \Gac\Helpers\Database::getConnection();
        } catch (\Throwable $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
        $this->controller = new EmailSubjectController();
        $this->subjectRepo = new EmailSubjectRepository();
    }

    protected function tearDown(): void
    {
        if ($this->createdId) {
            $this->subjectRepo->delete($this->createdId);
            $this->createdId = null;
        }
    }

    public function test_normalize_category_modo_viaje(): void
    {
        $this->assertSame('modo_viaje', $this->subjectRepo->normalizeCategory('modo_viaje'));
        $this->assertSame('modo_viaje', $this->subjectRepo->normalizeCategory('MODO_VIAJE'));
        $this->assertSame('general', $this->subjectRepo->normalizeCategory('otro'));
        $this->assertSame('general', $this->subjectRepo->normalizeCategory(null));
    }

    public function test_store_modo_viaje_subject_via_front_payload(): void
    {
        $platforms = (new PlatformRepository())->findAllEnabled();
        $this->assertNotEmpty($platforms, 'Debe existir al menos una plataforma habilitada');
        $platformId = (int) $platforms[0]['id'];

        $subjectLine = 'TEST Modo Viaje Exacto ' . date('YmdHis');

        // Mismo payload que envía el modal del front (FormData → JSON)
        $payload = [
            'platform_id' => $platformId,
            'subject_line' => $subjectLine,
            'category' => 'modo_viaje',
        ];

        $result = $this->controller->storeFromPayload($payload);

        $this->assertSame(201, $result['code']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('modo_viaje', $result['body']['category']);
        $this->assertSame($subjectLine, $result['body']['subject_line']);
        $this->assertNotEmpty($result['body']['id']);

        $this->createdId = (int) $result['body']['id'];

        $lines = $this->subjectRepo->getModoViajeSubjectLines();
        $this->assertContains($subjectLine, $lines, 'El asunto debe quedar disponible para consulta / lectura exacta');
    }

    public function test_store_rejects_empty_subject_like_front_validation(): void
    {
        $result = $this->controller->storeFromPayload([
            'platform_id' => 1,
            'subject_line' => '',
            'category' => 'modo_viaje',
        ]);
        $this->assertSame(400, $result['code']);
        $this->assertFalse($result['body']['success']);
    }
}
