<?php
/**
 * GAC - Controlador de Códigos
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Services\Code\CodeService;

class CodeController
{
    /**
     * Servicio de códigos
     */
    private CodeService $codeService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->codeService = new CodeService();
    }

    /**
     * Mostrar página de consulta de códigos
     */
    public function consult(Request $request): void
    {
        // Obtener plataformas disponibles desde BD
        $platforms = $this->codeService->getEnabledPlatforms();

        // Si es POST, procesar consulta
        if ($request->method() === 'POST') {
            $this->processConsult($request);
            return;
        }

        // Mostrar vista de consulta
        $this->renderView('codes/consult', [
            'platforms' => $platforms,
            'title' => 'Consulta tu Código'
        ]);
    }

    /**
     * Procesar consulta de código
     */
    private function processConsult(Request $request): void
    {
        $email = $request->input('email', '');
        $username = $request->input('username', '');
        $platform = $request->input('platform', '');

        // Validación básica
        if (empty($email) || empty($username) || empty($platform)) {
            json_response([
                'success' => false,
                'message' => 'Por favor completa todos los campos'
            ], 400);
            return;
        }

        // Consultar código usando el servicio
        $result = $this->codeService->consultCode($platform, $email, $username);

        // Determinar código HTTP
        $httpCode = $result['success'] ? 200 : 404;
        
        json_response($result, $httpCode);
    }

    /**
     * API endpoint para consulta AJAX
     */
    public function apiConsult(Request $request): void
    {
        // Solo aceptar POST
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        // Obtener datos del JSON body
        $email = $request->input('email', '');
        $username = $request->input('username', '');
        $platform = $request->input('platform', '');

        // Validación básica
        if (empty($email) || empty($username) || empty($platform)) {
            json_response([
                'success' => false,
                'message' => 'Por favor completa todos los campos'
            ], 400);
            return;
        }

        // Consultar código usando el servicio
        $result = $this->codeService->consultCode($platform, $email, $username);

        // Determinar código HTTP
        $httpCode = $result['success'] ? 200 : 404;
        
        json_response($result, $httpCode);
    }

    /**
     * Listar códigos recibidos (vista administrativa)
     */
    public function index(Request $request): void
    {
        $page = (int)$request->get('page', 1);
        $perPage = (int)$request->get('per_page', 15);
        $search = $request->get('search', '');

        $validPerPage = [15, 30, 60, 100, 0];
        if (!in_array($perPage, $validPerPage)) {
            $perPage = 15;
        }

        $codeRepository = new \Gac\Repositories\CodeRepository();
        $paginationData = $codeRepository->searchConsumedCodes($page, $perPage, $search);
        
        $this->renderView('admin/codes/index', [
            'title' => 'Registro de Accesos',
            'codes' => $paginationData['data'],
            'total_records' => $paginationData['total'],
            'current_page' => $paginationData['page'],
            'per_page' => $paginationData['per_page'],
            'total_pages' => $paginationData['total_pages'],
            'search_query' => $search,
            'valid_per_page' => $validPerPage
        ]);
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
