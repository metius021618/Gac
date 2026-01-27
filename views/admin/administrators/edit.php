<?php
/**
 * GAC - Vista de Edición de Administrador
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Editar Administrador</h1>
        <p class="admin-subtitle">Modifica los datos del administrador</p>
    </div>

    <div class="admin-content">
        <div class="admin-card">
            <form id="editAdminForm">
                <input type="hidden" id="adminId" name="id" value="<?= htmlspecialchars($admin['id']) ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?= htmlspecialchars($admin['username']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="active" class="form-label">Estado</label>
                    <select id="active" name="active" class="form-select">
                        <option value="1" <?= $admin['active'] ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= !$admin['active'] ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>

                <div class="form-actions">
                    <a href="/admin/administrators" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Editar Administrador';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css'];
$additional_js = ['/assets/js/admin/administrators_edit.js'];

require base_path('views/layouts/main.php');
?>
