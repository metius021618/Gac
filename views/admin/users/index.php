<?php
/**
 * GAC - Vista de Usuarios (Revendedores)
 * Solo muestra usuarios auto-generados para revendedores
 * con sus cuentas asignadas.
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Usuarios (Revendedores)</h1>
        <p class="admin-subtitle">
            Listado de usuarios que tienen 10 cuentas o más asignadas. Desde aquí puedes activar/desactivar o eliminar revendedores.
        </p>
    </div>

    <div class="admin-content">
        <div class="table-controls">
            <div class="search-input-wrapper">
                <span class="search-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" id="searchInput" class="search-input" placeholder="Buscar por usuario o email..." value="<?= htmlspecialchars($search_query) ?>">
            </div>
            <div class="per-page-selector">
                <label for="perPage">Mostrar:</label>
                <select id="perPage" class="form-select">
                    <?php foreach ($valid_per_page as $option): ?>
                        <option value="<?= $option ?>" <?= $per_page == $option ? 'selected' : '' ?>>
                            <?= $option == 0 ? 'Todos' : $option ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table class="admin-table" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Cuentas</th>
                        <th>Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <p class="empty-message">No hay usuarios revendedores registrados</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr data-id="<?= $user['id'] ?>">
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                <td><?= (int) ($user['accounts_count'] ?? 0) ?></td>
                                <td>
                                    <form method="post" action="/admin/users/toggle-active">
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <input type="hidden" name="active" value="<?= $user['active'] ? 0 : 1 ?>">
                                        <label class="toggle-switch">
                                            <input type="checkbox"
                                                   class="toggle-input"
                                                   <?= $user['active'] ? 'checked' : '' ?>
                                                   onchange="this.form.submit()">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </form>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <form method="post"
                                              action="/admin/users/delete"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este usuario revendedor?');">
                                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar usuario">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación simple -->
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando <?= min($per_page > 0 ? $per_page : $total_records, $total_records) ?> de <?= $total_records ?> usuarios
            </div>
            <div class="pagination-controls">
                <?php if ($current_page > 1): ?>
                    <button class="btn btn-secondary btn-sm" data-page="<?= $current_page - 1 ?>">Anterior</button>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <button class="btn btn-sm <?= $i == $current_page ? 'btn-primary' : 'btn-secondary' ?>" data-page="<?= $i ?>"><?= $i ?></button>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <button class="btn btn-secondary btn-sm" data-page="<?= $current_page + 1 ?>">Siguiente</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Usuarios (Revendedores)';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css'];
$additional_js = ['/assets/js/admin/search-ajax.js'];

require base_path('views/layouts/main.php');
?>
