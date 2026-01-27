<?php
/**
 * GAC - Controlador de Administradores
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\UserRepository;

class AdminController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Listar administradores
     */
    public function index(Request $request): void
    {
        $administrators = $this->userRepository->findAllAdministrators();
        
        $this->renderView('admin/administrators/index', [
            'title' => 'Administradores',
            'administrators' => $administrators
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Request $request): void
    {
        $id = (int)$request->get('id');
        
        if (!$id) {
            header('Location: /admin/administrators');
            exit;
        }

        $admin = $this->userRepository->findById($id);
        
        if (!$admin) {
            header('Location: /admin/administrators');
            exit;
        }

        $this->renderView('admin/administrators/edit', [
            'title' => 'Editar Administrador',
            'admin' => $admin
        ]);
    }

    /**
     * Actualizar administrador
     */
    public function update(Request $request): void
    {
        $id = (int)$request->input('id');
        $username = $request->input('username');
        $email = $request->input('email');
        $active = (int)$request->input('active', 1);

        if (!$id || !$username || !$email) {
            json_response([
                'success' => false,
                'message' => 'Datos incompletos'
            ], 400);
            return;
        }

        $updated = $this->userRepository->update($id, [
            'username' => $username,
            'email' => $email,
            'active' => $active
        ]);

        if ($updated) {
            json_response([
                'success' => true,
                'message' => 'Administrador actualizado correctamente'
            ]);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al actualizar administrador'
            ], 500);
        }
    }

    /**
     * Actualizar contraseña
     */
    public function updatePassword(Request $request): void
    {
        $id = (int)$request->input('id');
        $newPassword = $request->input('new_password');
        $confirmPassword = $request->input('confirm_password');

        if (!$id || !$newPassword || !$confirmPassword) {
            json_response([
                'success' => false,
                'message' => 'Datos incompletos'
            ], 400);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            json_response([
                'success' => false,
                'message' => 'Las contraseñas no coinciden'
            ], 400);
            return;
        }

        if (strlen($newPassword) < 6) {
            json_response([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ], 400);
            return;
        }

        // Encriptar contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $updated = $this->userRepository->updatePassword($id, $hashedPassword);

        if ($updated) {
            json_response([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ]);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al actualizar contraseña'
            ], 500);
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
