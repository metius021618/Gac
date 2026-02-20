<?php
/**
 * GAC - Vista de Lista de Administradores
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header admin-header--with-action">
        <h1 class="admin-title">Administradores</h1>
        <button type="button" class="btn btn-primary" id="btnNewUser" title="Agregar usuario">
            + Usuario
        </button>
    </div>

    <div class="admin-content">
        <div class="table-container">
            <table class="admin-table" id="administratorsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($administrators)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <p class="empty-message">No hay administradores registrados</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($administrators as $admin): ?>
                            <tr data-id="<?= $admin['id'] ?>">
                                <td><?= htmlspecialchars($admin['id']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($admin['username']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($admin['role_display_name'] ?? $admin['role_name']) ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $admin['active'] ? 'active' : 'inactive' ?>">
                                        <?= $admin['active'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'Nunca' ?>
                                </td>
                                <td>
                                    <?= $admin['created_at'] ? date('d/m/Y H:i', strtotime($admin['created_at'])) : '-' ?>
                                </td>
                                <td class="actions-cell">
                                    <button class="btn-icon btn-edit" 
                                            data-id="<?= $admin['id'] ?>"
                                            onclick="window.location.href='/admin/administrators/edit?id=<?= $admin['id'] ?>'"
                                            title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                    <button class="btn-icon btn-password" 
                                            data-id="<?= $admin['id'] ?>"
                                            data-username="<?= htmlspecialchars($admin['username']) ?>"
                                            title="Editar Contraseña">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div id="newUserModal" class="modal hidden">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-container--wide">
        <div class="modal-header">
            <h2 class="modal-title">Nuevo Usuario</h2>
            <button type="button" class="modal-close" id="closeNewUserModal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-content">
            <form id="newUserForm">
                <div class="form-row form-row--two">
                    <div class="form-group">
                        <label for="newUserUsername" class="form-label">Usuario</label>
                        <input type="text" id="newUserUsername" name="username" class="form-input" required placeholder="Nombre de usuario">
                    </div>
                    <div class="form-group">
                        <label for="newUserPassword" class="form-label">Contraseña</label>
                        <input type="password" id="newUserPassword" name="password" class="form-input" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>
                <div class="form-group">
                    <label for="newUserRole" class="form-label">Rol</label>
                    <select id="newUserRole" name="role_id" class="form-input" required>
                        <option value="">Seleccione un rol</option>
                        <?php foreach ($roles ?? [] as $role): ?>
                            <option value="<?= (int)$role['id'] ?>" data-name="<?= htmlspecialchars($role['name'] ?? '') ?>"><?= htmlspecialchars($role['display_name'] ?? $role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vista que podrá ver el usuario</label>
                    <div class="preview-view-box" id="previewViewBox">
                        <p class="preview-view-title" id="previewViewTitle">Vista de: <span id="previewUsernamePlaceholder">—</span></p>
                        <div class="preview-dashboard" id="previewDashboard">
                            <p class="preview-dashboard-empty" id="previewDashboardEmpty">Seleccione un rol para ver la vista previa.</p>
                            <div class="preview-dashboard-content hidden" id="previewDashboardContent"></div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelNewUserBtn">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar contraseña -->
<div id="passwordModal" class="modal hidden">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Cambiar Contraseña</h2>
            <button type="button" class="modal-close" id="closePasswordModal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-content">
            <form id="passwordForm">
                <input type="hidden" id="passwordUserId" name="id">
                <div class="form-group">
                    <label for="newPassword" class="form-label">Nueva Contraseña</label>
                    <input type="password" id="newPassword" name="new_password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirmPassword" class="form-label">Confirmar Contraseña</label>
                    <input type="password" id="confirmPassword" name="confirm_password" class="form-input" required minlength="6">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelPasswordBtn">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Administradores';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css'];
$additional_js = ['/assets/js/admin/administrators.js'];

require base_path('views/layouts/main.php');
?>
