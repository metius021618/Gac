<?php
/**
 * GAC - Controlador Vista Hogar (Consulta código Netflix)
 * 
 * Vista simplificada para consultar solo códigos temporales de Netflix.
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Services\Code\CodeService;

class HogarController
{
    private CodeService $codeService;

    public function __construct()
    {
        $this->codeService = new CodeService();
    }

    /**
     * Mostrar página de consulta de código Netflix
     */
    public function index(Request $request): void
    {
        if ($request->method() === 'POST') {
            $this->processConsult($request);
            return;
        }

        $this->renderView('hogar/index', [
            'title' => 'Consulta tu código Netflix'
        ]);
    }

    /**
     * Procesar consulta de código temporal Netflix
     */
    private function processConsult(Request $request): void
    {
        $email = trim($request->input('email', ''));

        if (empty($email)) {
            json_response([
                'success' => false,
                'message' => 'Por favor ingresa tu correo electrónico'
            ], 400);
            return;
        }

        $result = $this->codeService->consultCodeNetflix($email);
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
