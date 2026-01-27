<?php
/**
 * GAC - Controlador del Dashboard
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\EmailAccountRepository;
use Gac\Repositories\PlatformRepository;
use Gac\Repositories\UserRepository;
use Gac\Repositories\CodeRepository;

class DashboardController
{
    private EmailAccountRepository $emailAccountRepo;
    private PlatformRepository $platformRepo;
    private UserRepository $userRepo;
    private CodeRepository $codeRepo;

    public function __construct()
    {
        $this->emailAccountRepo = new EmailAccountRepository();
        $this->platformRepo = new PlatformRepository();
        $this->userRepo = new UserRepository();
        $this->codeRepo = new CodeRepository();
    }

    /**
     * Mostrar dashboard principal
     */
    public function index(Request $request): void
    {
        $stats = [
            'email_accounts' => $this->getEmailAccountsCount(),
            'platforms_active' => $this->getPlatformsActiveCount(),
            'administrators' => $this->getAdministratorsCount()
        ];

        $this->renderView('admin/dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats
        ]);
    }

    /**
     * Obtener cantidad de correos agregados
     */
    private function getEmailAccountsCount(): int
    {
        try {
            return $this->emailAccountRepo->countAll();
        } catch (\Exception $e) {
            error_log("Error al contar correos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener cantidad de plataformas activas
     */
    private function getPlatformsActiveCount(): int
    {
        try {
            return $this->platformRepo->countAllEnabled();
        } catch (\Exception $e) {
            error_log("Error al contar plataformas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener cantidad de administradores
     */
    private function getAdministratorsCount(): int
    {
        try {
            return $this->userRepo->countAdministrators();
        } catch (\Exception $e) {
            error_log("Error al contar administradores: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Renderizar vista
     */
    private function renderView(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = base_path('views/' . str_replace('.', '/', $view) . '.php');
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            http_response_code(404);
            echo "Vista no encontrada: {$view}";
        }
    }
}
