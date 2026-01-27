<?php
/**
 * GAC - Vista de Registro de Accesos (Códigos Consumidos)
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Registro de Accesos</h1>
        <p class="admin-subtitle">Historial de códigos consultados y consumidos</p>
    </div>

    <div class="admin-content">
        <div class="table-controls">
            <div class="search-input-wrapper">
                <span class="search-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" id="searchInput" class="search-input" placeholder="Buscar por código, usuario, email o plataforma..." value="<?= htmlspecialchars($search_query) ?>">
                <?php if (!empty($search_query)): ?>
                    <button type="button" id="clearSearch" class="clear-search-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                <?php endif; ?>
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
            <table class="admin-table" id="accessLogTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Plataforma</th>
                        <th>Usuario</th>
                        <th>Email Consultado</th>
                        <th>Destinatario</th>
                        <th>Fecha Consulta</th>
                        <th>Fecha Recepción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($codes)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <p class="empty-message">No hay registros de accesos</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($codes as $code): ?>
                            <tr data-id="<?= $code['id'] ?>">
                                <td><?= htmlspecialchars($code['id']) ?></td>
                                <td>
                                    <code style="font-family: 'Courier New', monospace; background: rgba(255, 255, 255, 0.1); padding: 2px 6px; border-radius: 4px;">
                                        <?= htmlspecialchars($code['code']) ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($code['platform_display_name']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($code['consumed_by_username'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($code['consumed_by_email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($code['recipient_email'] ?? '-') ?></td>
                                <td>
                                    <?= $code['consumed_at'] ? date('d/m/Y H:i', strtotime($code['consumed_at'])) : '-' ?>
                                </td>
                                <td>
                                    <?= $code['received_at'] ? date('d/m/Y H:i', strtotime($code['received_at'])) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando <?= min($per_page > 0 ? $per_page : $total_records, $total_records) ?> de <?= $total_records ?> registros
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

$title = $title ?? 'Registro de Accesos';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css'];
$additional_js = ['/assets/js/admin/search-ajax.js', '/assets/js/admin/codes.js'];

require base_path('views/layouts/main.php');
?>
