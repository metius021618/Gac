<?php
/**
 * GAC - Controlador de Asuntos de Email
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\EmailSubjectRepository;
use Gac\Repositories\PlatformRepository;

class EmailSubjectController
{
    private EmailSubjectRepository $emailSubjectRepository;
    private PlatformRepository $platformRepository;

    public function __construct()
    {
        $this->emailSubjectRepository = new EmailSubjectRepository();
        $this->platformRepository = new PlatformRepository();
    }

    /**
     * Listar asuntos de email con búsqueda y paginación
     */
    public function index(Request $request): void
    {
        $page = (int)$request->get('page', 1);
        $perPageRaw = $request->get('per_page', '15');
        $perPage = $perPageRaw === 'all' ? 0 : (int)$perPageRaw;
        $search = $request->get('search', '');
        if ($search === '' && !empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $q);
            $search = isset($q['search']) ? (string)$q['search'] : '';
        }
        if ($search === '' && !empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
            parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?: '', $q);
            $search = isset($q['search']) ? (string)$q['search'] : $search;
        }

        $category = $this->emailSubjectRepository->normalizeCategory((string) $request->get('category', 'general'));

        $validPerPage = [10, 15, 30, 60, 100, 0]; // 0 para "Todos"
        if (!in_array($perPage, $validPerPage)) {
            $perPage = 15; // Default
        }

        $logFile = base_path('logs' . DIRECTORY_SEPARATOR . 'email_subjects_search.log');
        $logLine = date('Y-m-d H:i:s') . ' [email-subjects] GET=' . json_encode($_GET) . ' REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '') . ' isAjax=' . ($request->isAjax() ? '1' : '0') . ' search="' . $search . '" category=' . $category . ' page=' . $page . ' per_page=' . $perPage . "\n";
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        $paginationData = $this->emailSubjectRepository->searchAndPaginate($search, $page, $perPage, $category);

        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' [email-subjects] total=' . $paginationData['total'] . ' rows=' . count($paginationData['data']) . "\n", FILE_APPEND | LOCK_EX);

        // Si es petición AJAX, devolver solo la tabla y paginación
        if ($request->isAjax()) {
            ob_start();
            extract([
                'subjects' => $paginationData['data'],
                'total_records' => $paginationData['total'],
                'current_page' => $paginationData['page'],
                'per_page' => $paginationData['per_page'],
                'total_pages' => $paginationData['total_pages'],
                'search_query' => $search,
                'valid_per_page' => $validPerPage,
                'category_filter' => $category,
            ]);
            require base_path('views/admin/email_subjects/_table.php');
            $tableHtml = ob_get_clean();
            
            // Envolver en admin-content para que SearchAJAX.updateTableContent funcione
            echo '<div class="admin-content">' . $tableHtml . '</div>';
            return;
        }
        
        $this->renderView('admin/email_subjects/index', [
            'title' => 'Asuntos de correo',
            'subjects' => $paginationData['data'],
            'total_records' => $paginationData['total'],
            'current_page' => $paginationData['page'],
            'per_page' => $paginationData['per_page'],
            'total_pages' => $paginationData['total_pages'],
            'search_query' => $search,
            'valid_per_page' => $validPerPage,
            'category_filter' => $category,
        ]);
    }

    /**
     * Mostrar formulario para crear nuevo asunto
     */
    public function create(Request $request): void
    {
        $platforms = $this->platformRepository->findAllEnabled();
        
        $this->renderView('admin/email_subjects/form', [
            'title' => 'Nuevo Asunto',
            'platforms' => $platforms,
            'mode' => 'create',
            'subject' => null
        ]);
    }

    /**
     * Guardar nuevo asunto (mismo payload que el front: platform_id, subject_line, category).
     */
    public function store(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $result = $this->storeFromPayload($request->all());
        json_response($result['body'], $result['code']);
    }

    /**
     * Lógica de alta usada por el front (JSON) y por tests unitarios.
     * No inserta directo en BD: pasa por validaciones del controlador + repository->save.
     *
     * @param array $payload {platform_id, subject_line, category?}
     * @return array{body: array, code: int}
     */
    public function storeFromPayload(array $payload): array
    {
        $platformId = (int)($payload['platform_id'] ?? 0);
        $subjectLine = trim((string)($payload['subject_line'] ?? ''));
        $category = $this->emailSubjectRepository->normalizeCategory((string)($payload['category'] ?? 'general'));
        $bodyMatch = trim((string)($payload['body_match'] ?? ''));

        if ($platformId <= 0 || $subjectLine === '') {
            return [
                'code' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Todos los campos son requeridos'
                ]
            ];
        }

        if ($this->emailSubjectRepository->isSpecialCategory($category) && $bodyMatch === '') {
            return [
                'code' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Los asuntos especiales requieren el contenido del cuerpo del correo'
                ]
            ];
        }

        $platform = $this->platformRepository->findById($platformId);
        if (!$platform) {
            return [
                'code' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'La plataforma seleccionada no existe'
                ]
            ];
        }

        $subjectId = $this->emailSubjectRepository->save([
            'platform_id' => $platformId,
            'subject_line' => $subjectLine,
            'category' => $category,
            'body_match' => $bodyMatch,
        ]);

        if ($subjectId) {
            return [
                'code' => 201,
                'body' => [
                    'success' => true,
                    'message' => 'Asunto agregado correctamente',
                    'id' => $subjectId,
                    'category' => $category,
                    'subject_line' => $subjectLine,
                ]
            ];
        }

        $isDuplicate = EmailSubjectRepository::getLastError() === 'duplicate';
        return [
            'code' => $isDuplicate ? 409 : 500,
            'body' => [
                'success' => false,
                'message' => $isDuplicate
                    ? 'Ya existe un asunto con el mismo texto para esta plataforma.'
                    : 'Error al guardar el asunto.'
            ]
        ];
    }

    /**
     * Mostrar formulario para editar asunto
     */
    public function edit(Request $request): void
    {
        $id = (int)$request->get('id', 0);
        
        $subject = $this->emailSubjectRepository->findById($id);
        
        if (!$subject) {
            http_response_code(404);
            echo "Asunto no encontrado";
            return;
        }

        $platforms = $this->platformRepository->findAllEnabled();

        $this->renderView('admin/email_subjects/form', [
            'title' => 'Editar Asunto',
            'platforms' => $platforms,
            'mode' => 'edit',
            'subject' => $subject
        ]);
    }

    /**
     * Actualizar asunto
     */
    public function update(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $id = (int)$request->input('id', 0);
        $platformId = (int)$request->input('platform_id', 0);
        $subjectLine = trim($request->input('subject_line', ''));
        $category = $this->emailSubjectRepository->normalizeCategory((string)$request->input('category', 'general'));
        $bodyMatch = trim((string)$request->input('body_match', ''));

        // Validaciones
        if ($id <= 0 || $platformId <= 0 || empty($subjectLine)) {
            json_response([
                'success' => false,
                'message' => 'Todos los campos son requeridos'
            ], 400);
            return;
        }

        if ($this->emailSubjectRepository->isSpecialCategory($category) && $bodyMatch === '') {
            json_response([
                'success' => false,
                'message' => 'Los asuntos especiales requieren el contenido del cuerpo del correo'
            ], 400);
            return;
        }

        // Verificar que el asunto exista
        $existingSubject = $this->emailSubjectRepository->findById($id);
        if (!$existingSubject) {
            json_response([
                'success' => false,
                'message' => 'Asunto no encontrado'
            ], 404);
            return;
        }

        // Verificar que la plataforma exista
        $platform = $this->platformRepository->findById($platformId);
        if (!$platform) {
            json_response([
                'success' => false,
                'message' => 'La plataforma seleccionada no existe'
            ], 400);
            return;
        }

        // Actualizar asunto
        $data = [
            'platform_id' => $platformId,
            'subject_line' => $subjectLine,
            'category' => $category,
            'body_match' => $bodyMatch,
        ];

        $updated = $this->emailSubjectRepository->update($id, $data);

        if ($updated) {
            json_response([
                'success' => true,
                'message' => 'Asunto actualizado correctamente'
            ], 200);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al actualizar el asunto'
            ], 500);
        }
    }

    /**
     * Eliminar asunto
     */
    public function destroy(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $id = (int)$request->input('id', 0);
        
        if ($id <= 0) {
            json_response([
                'success' => false,
                'message' => 'ID de asunto inválido'
            ], 400);
            return;
        }

        $deleted = $this->emailSubjectRepository->delete($id);

        if ($deleted) {
            json_response([
                'success' => true,
                'message' => 'Asunto eliminado correctamente'
            ], 200);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al eliminar el asunto'
            ], 500);
        }
    }

    /**
     * Listar emails con permiso can_view_special (para panel de asuntos especiales).
     */
    public function specialAccessList(Request $request): void
    {
        $repo = new \Gac\Repositories\UserAccessRepository();
        $search = trim((string) $request->get('search', ''));
        $rows = $repo->listSpecialViewAccess($search, 200);
        json_response(['success' => true, 'data' => $rows], 200);
    }

    /**
     * Activar/desactivar can_view_special para un email (todas sus filas).
     */
    public function specialAccessToggle(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        $email = strtolower(trim((string) $request->input('email', '')));
        $enabled = (int) $request->input('enabled', 0) === 1;
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Email inválido'], 400);
            return;
        }
        $repo = new \Gac\Repositories\UserAccessRepository();
        $ok = $repo->setCanViewSpecialByEmail($email, $enabled);
        json_response([
            'success' => $ok,
            'message' => $ok
                ? ($enabled ? 'Usuario autorizado para ver códigos especiales' : 'Permiso de especiales quitado')
                : 'No se pudo actualizar el permiso',
        ], $ok ? 200 : 500);
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
            echo "Vista no encontrada: {$viewPath}";
        }
    }
}
