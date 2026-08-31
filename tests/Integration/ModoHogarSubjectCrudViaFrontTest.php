<?php
/**
 * Tests: alta de asuntos MODO HOGAR vía payload del front
 * (mismo JSON que email_subjects.js → POST /admin/email-subjects).
 */

namespace Gac\Tests\Integration;

use Gac\Controllers\EmailSubjectController;
use Gac\Repositories\EmailSubjectRepository;
use Gac\Repositories\PlatformRepository;
use PHPUnit\Framework\TestCase;

class ModoHogarSubjectCrudViaFrontTest extends TestCase
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

    public function test_store_modo_hogar_subject_via_front_payload(): void
    {
        $platforms = (new PlatformRepository())->findAllEnabled();
        $this->assertNotEmpty($platforms, 'Debe existir al menos una plataforma habilitada');
        $platformId = (int) $platforms[0]['id'];

        $subjectLine = 'TEST Modo Hogar Exacto ' . date('YmdHis');

        $payload = [
            'platform_id' => $platformId,
            'subject_line' => $subjectLine,
            'category' => 'modo_hogar',
        ];

        $result = $this->controller->storeFromPayload($payload);

        $this->assertSame(201, $result['code']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('modo_hogar', $result['body']['category']);
        $this->assertSame($subjectLine, $result['body']['subject_line']);
        $this->assertNotEmpty($result['body']['id']);

        $this->createdId = (int) $result['body']['id'];

        $lines = $this->subjectRepo->getModoHogarSubjectLines();
        $this->assertContains($subjectLine, $lines, 'El asunto debe quedar disponible para consulta /hogar');
        $this->assertNotContains($subjectLine, $this->subjectRepo->getModoViajeSubjectLines());
    }
}
