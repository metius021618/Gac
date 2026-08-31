<?php
/**
 * GAC - Controlador Vista Modo Viaje
 *
 * Consulta correos cuyos asuntos están registrados en categoría MODO VIAJE.
 *
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Services\Code\CodeService;

class MViajeController
{
    private CodeService $codeService;

    public function __construct()
    {
        $this->codeService = new CodeService();
    }

    public function index(Request $request): void
    {
        if ($request->method() === 'POST') {
            $this->processConsult($request);
            return;
        }

        $this->renderView('hogar/index', [
            'title' => 'Actualizar hogar',
            'initial_mode' => 'viaje',
        ]);
    }

    private function processConsult(Request $request): void
    {
        $email = trim($request->input('email', ''));

        if ($email === '') {
            json_response([
                'success' => false,
                'message' => 'Por favor ingresa tu correo electrónico'
            ], 400);
            return;
        }

        $result = $this->codeService->consultCodeModoViaje($email);
        $httpCode = $result['success'] ? 200 : 404;
        json_response($result, $httpCode);
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
