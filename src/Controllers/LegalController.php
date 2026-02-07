<?php
/**
 * GAC - Controlador de páginas legales
 * Política de privacidad y condiciones del servicio
 *
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;

class LegalController
{
    /**
     * Política de privacidad
     */
    public function politicaPrivacidad(Request $request): void
    {
        $this->renderLegalView('legal.politica-privacidad', 'Política de Privacidad');
    }

    /**
     * Condiciones del servicio
     */
    public function condicionesServicio(Request $request): void
    {
        $this->renderLegalView('legal.condiciones-servicio', 'Condiciones del Servicio');
    }

    private function renderLegalView(string $view, string $title): void
    {
        $viewPath = base_path('views/' . str_replace('.', '/', $view) . '.php');
        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo "Página no encontrada.";
            return;
        }
        require $viewPath;
    }
}
