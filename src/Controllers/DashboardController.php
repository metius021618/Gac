<?php
/**
 * GAC - Controlador del Dashboard
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\PlatformRepository;
use Gac\Repositories\UserRepository;
use Gac\Repositories\UserAccessRepository;

class DashboardController
{
    private UserAccessRepository $userAccessRepo;
    private PlatformRepository $platformRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userAccessRepo = new UserAccessRepository();
        $this->platformRepo = new PlatformRepository();
        $this->userRepo = new UserRepository();
    }

    /**
     * Mostrar dashboard principal
     */
    public function index(Request $request): void
    {
        $stats = [
            'email_accounts' => $this->getEmailAccountsCount(),
            'platforms_active' => $this->getPlatformsActiveCount(),
            'administrators' => $this->getAdministratorsCount(),
            'gmail_count' => $this->getEmailCountByDomain(['gmail.com']),
            'outlook_count' => $this->getEmailCountByDomain(['outlook.com', 'hotmail.com', 'live.com']),
            'pocoyoni_count' => $this->getEmailCountByDomain(['pocoyoni.com'])
        ];

        $this->renderView('admin/dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats
        ]);
    }

    /**
     * Obtener cantidad de correos registrados (tabla user_access, mismo dato que la vista Correos registrados)
     */
    private function getEmailAccountsCount(): int
    {
        try {
            return $this->userAccessRepo->countAll();
        } catch (\Exception $e) {
            error_log("Error al contar correos registrados: " . $e->getMessage());
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
     * Contar correos por dominio(s) en user_access
     */
    private function getEmailCountByDomain(array $domains): int
    {
        try {
            return $this->userAccessRepo->countByDomains($domains);
        } catch (\Exception $e) {
            error_log("Error al contar correos por dominio: " . $e->getMessage());
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
