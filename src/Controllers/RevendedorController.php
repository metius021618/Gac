<?php
/**
 * GAC - Controlador de Revendedor
 *
 * Dashboard y vistas para usuarios de rol REVENDEDOR:
 * - /revendedor/dashboard
 * - /revendedor/lista-cuentas
 * - /revendedor/accesos
 *
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Helpers\Database;
use Gac\Repositories\UserAccessRepository;
use Gac\Repositories\UserAccessSubuserRepository;

class RevendedorController
{
    private UserAccessRepository $userAccessRepository;
    private UserAccessSubuserRepository $subuserRepository;

    public function __construct()
    {
        $this->userAccessRepository = new UserAccessRepository();
        $this->subuserRepository = new UserAccessSubuserRepository();
    }

    /**
     * Dashboard del revendedor: muestra 2 cards (Lista de cuentas, Accesos).
     */
    public function dashboard(Request $request): void
    {
        $user = $this->ensureReseller();

        $this->renderView('revendedor/dashboard', [
            'title' => 'Panel de Revendedor',
            'revendedor' => $user,
        ]);
    }

    /**
     * Lista de cuentas del revendedor (solo sus cuentas vendidas).
     * Columnas: Correo - Plataforma - Asignado (según tenga subusuarios).
     */
    public function listaCuentas(Request $request): void
    {
        $user = $this->ensureReseller();
        $username = $user['username'] ?? '';

        // Cuentas donde password = username del revendedor
        $accesses = $this->userAccessRepository->findByOwnerUsername($username);

        // Calcular si cada acceso tiene subusuarios y listarlos
        $rows = [];
        foreach ($accesses as $row) {
            $accessId = (int) ($row['id'] ?? 0);
            if ($accessId <= 0) {
                continue;
            }
            $subusers = $this->subuserRepository->findByAccessId($accessId);
            $rows[] = [
                'access' => $row,
                'subusers' => $subusers,
                'asignado' => !empty($subusers),
            ];
        }

        $this->renderView('revendedor/lista_cuentas', [
            'title' => 'Mis cuentas vendidas',
            'revendedor' => $user,
            'rows' => $rows,
        ]);
    }

    /**
     * Formulario de creación de subusuarios (vista Accesos).
     * GET: muestra formulario.
     * POST (accesosStore): procesa creación.
     */
    public function accesos(Request $request): void
    {
        $user = $this->ensureReseller();
        $username = $user['username'] ?? '';

        // Cuentas propias para poblar combos
        $accesses = $this->userAccessRepository->findByOwnerUsername($username);

        // Mapear por correo → plataformas
        $emailMap = [];
        foreach ($accesses as $row) {
            $email = $row['email'] ?? '';
            if ($email === '') {
                continue;
            }
            $emailMap[$email]['email'] = $email;
            $emailMap[$email]['platforms'] = $emailMap[$email]['platforms'] ?? [];
            $emailMap[$email]['platforms'][] = [
                'id' => (int) $row['platform_id'],
                'name' => $row['platform_name'] ?? '',
                'access_id' => (int) $row['id'],
            ];
        }

        $flash = $_SESSION['revendedor_flash'] ?? null;
        $flashType = $_SESSION['revendedor_flash_type'] ?? 'success';
        unset($_SESSION['revendedor_flash'], $_SESSION['revendedor_flash_type']);

        $this->renderView('revendedor/accesos', [
            'title' => 'Accesos',
            'revendedor' => $user,
            'email_map' => $emailMap,
            'flash' => $flash,
            'flash_type' => $flashType,
        ]);
    }

    /**
     * Procesar creación de subusuario (Accesos).
     */
    public function accesosStore(Request $request): void
    {
        $user = $this->ensureReseller();
        $username = $user['username'] ?? '';

        if ($request->method() !== 'POST') {
            redirect('/revendedor/accesos');
            return;
        }

        $email = trim($request->input('email', ''));
        $platformId = (int) $request->input('platform_id', 0);
        $subUsername = trim($request->input('sub_username', ''));

        if ($email === '' || $platformId <= 0 || $subUsername === '') {
            $_SESSION['revendedor_flash'] = 'Por favor completa todos los campos.';
            $_SESSION['revendedor_flash_type'] = 'error';
            redirect('/revendedor/accesos');
            return;
        }

        if (mb_strlen($subUsername) < 3) {
            $_SESSION['revendedor_flash'] = 'El usuario adicional debe tener al menos 3 caracteres.';
            $_SESSION['revendedor_flash_type'] = 'error';
            redirect('/revendedor/accesos');
            return;
        }

        // Buscar acceso propiedad del revendedor para ese correo + plataforma
        $access = $this->userAccessRepository->findOwnedByEmailAndPlatform($username, $email, $platformId);
        if (!$access) {
            $_SESSION['revendedor_flash'] = 'No se encontró una cuenta asignada para ese correo y plataforma.';
            $_SESSION['revendedor_flash_type'] = 'error';
            redirect('/revendedor/accesos');
            return;
        }

        $accessId = (int) $access['id'];

        // Crear subusuario
        $createdId = $this->subuserRepository->create($accessId, $subUsername);
        if ($createdId === null) {
            $_SESSION['revendedor_flash'] = 'No se pudo crear el usuario adicional. Intenta nuevamente.';
            $_SESSION['revendedor_flash_type'] = 'error';
            redirect('/revendedor/accesos');
            return;
        }

        $_SESSION['revendedor_flash'] = 'Usuario adicional creado correctamente.';
        $_SESSION['revendedor_flash_type'] = 'success';
        redirect('/revendedor/accesos');
    }

    /**
     * Eliminar un subusuario (solo revendedor sobre sus propias cuentas).
     */
    public function eliminarSubusuario(Request $request): void
    {
        $user = $this->ensureReseller();
        $username = $user['username'] ?? '';

        if ($request->method() !== 'POST') {
            redirect('/revendedor/lista-cuentas');
            return;
        }

        $subId = (int) $request->input('id', 0);
        if ($subId <= 0) {
            redirect('/revendedor/lista-cuentas');
            return;
        }

        // Validar que el subusuario pertenezca a un acceso del revendedor
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT sus.id, sus.user_access_id, ua.email, ua.password, ua.platform_id
            FROM user_access_subusers sus
            INNER JOIN user_access ua ON ua.id = sus.user_access_id
            WHERE sus.id = :id AND ua.password = :owner
            LIMIT 1
        ");
        $stmt->execute([
            ':id' => $subId,
            ':owner' => $username,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            redirect('/revendedor/lista-cuentas');
            return;
        }

        $this->subuserRepository->delete($subId);
        redirect('/revendedor/lista-cuentas');
    }

    /**
     * Asegurar que el usuario autenticado es un revendedor.
     * Devuelve datos básicos del usuario.
     */
    private function ensureReseller(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
            redirect('/login');
        }

        $userId = (int) $_SESSION['user_id'];
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email, u.role_id, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        if (!$user || strtoupper((string) ($user['role_name'] ?? '')) !== 'REVENDEDOR') {
            // No es revendedor, redirigir al dashboard normal
            redirect('/admin/dashboard');
        }

        return $user;
    }

    /**
     * Renderizar vista usando el layout principal.
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

