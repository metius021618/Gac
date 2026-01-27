<?php
/**
 * GAC - Controlador de Configuración
 */

namespace Gac\Controllers;

use Gac\Core\Request;

class SettingsController
{
    public function index(Request $request): void
    {
        $settingsRepository = new \Gac\Repositories\SettingsRepository();
        $allSubjects = $settingsRepository->getAllEmailSubjects();
        
        // Organizar por plataforma
        $platforms = ['netflix', 'disney', 'prime', 'spotify', 'crunchyroll', 'paramount', 'chatgpt', 'canva'];
        $subjectsByPlatform = [];
        
        foreach ($platforms as $platform) {
            $subjects = $settingsRepository->getEmailSubjectsForPlatform($platform);
            if (!empty($subjects)) {
                $subjectsByPlatform[$platform] = $subjects;
            }
        }
        
        $this->renderView('admin/settings/index', [
            'title' => 'Registro de Asuntos',
            'subjects_by_platform' => $subjectsByPlatform,
            'platforms' => $platforms
        ]);
    }

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
