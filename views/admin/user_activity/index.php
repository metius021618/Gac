<?php
/**
 * GAC - Vista Actividad de usuario (solo superadmin)
 * Tabla: USUARIO | ACCIÓN | DESCRIPCIÓN | FECHA | HORA
 */

$content = ob_start();
$activities = $activities ?? [];
$total_records = (int)($total_records ?? 0);
$current_page = (int)($current_page ?? 1);
$per_page = (int)($per_page ?? 15);
$total_pages = (int)($total_pages ?? 1);
$order = ($order ?? 'desc') === 'asc' ? 'asc' : 'desc';
$valid_per_page = $valid_per_page ?? [15, 30, 45, 60, 100];
$baseUrl = '/admin/user-activity';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Actividad de usuario
        </h1>
        <p class="admin-subtitle">Monitoreo de acciones de usuarios (agregar, editar, eliminar, asignar correos). Solo visible para superadmin.</p>
    </div>

    <div class="admin-content">
        <div class="table-controls user-activity-controls">
            <div class="table-controls-right">
                <div class="per-page-selector">
                    <label for="perPageSelect" class="per-page-label">Mostrar por página:</label>
                    <select id="perPageSelect" class="form-select user-activity-per-page">
                        <?php foreach ($valid_per_page as $option): ?>
                            <option value="<?= $option ?>" <?= $per_page === $option ? 'selected' : '' ?>>
                                <?= $option ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table class="admin-table" id="userActivityTable">
                <thead>
                    <tr>
                        <th style="width: 14%;">USUARIO</th>
                        <th style="width: 14%;">ACCIÓN</th>
                        <th style="width: 44%;">DESCRIPCIÓN</th>
                        <th style="width: 14%;">
                            <a href="<?= $baseUrl ?>?page=<?= $current_page ?>&per_page=<?= $per_page ?>&order=<?= $order === 'desc' ? 'asc' : 'desc' ?>" class="sortable-header sortable-header--date" id="sortDateLink">
                                FECHA
                                <?php if ($order === 'desc'): ?>
                                    <span class="sort-icon">▼</span>
                                <?php else: ?>
                                    <span class="sort-icon">▲</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th style="width: 14%;">HORA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <p class="empty-message">No hay actividad registrada.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $row): 
                            $created = $row['created_at'] ?? '';
                            $datePart = $created ? date('d/m/Y', strtotime($created)) : '—';
                            $timePart = $created ? date('H:i', strtotime($created)) : '—';
                            $actionLabel = \Gac\Repositories\UserActivityLogRepository::actionLabel($row['action'] ?? '');
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                                <td><span class="activity-tag activity-tag--<?= htmlspecialchars($row['action'] ?? '') ?>"><?= htmlspecialchars($actionLabel) ?></span></td>
                                <td class="activity-description"><?= nl2br($row['description'] ?? '') ?></td>
                                <td><?= $datePart ?></td>
                                <td><?= $timePart ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando <strong><?= $total_records === 0 ? 0 : (($current_page - 1) * $per_page) + 1 ?></strong> - <strong><?= min($current_page * $per_page, $total_records) ?></strong> de <strong><?= number_format($total_records) ?></strong> registros
            </div>
            <div class="pagination-controls">
                <a href="<?= $baseUrl ?>?page=<?= max(1, $current_page - 1) ?>&per_page=<?= $per_page ?>&order=<?= $order ?>" class="pagination-btn <?= $current_page <= 1 ? 'disabled' : '' ?>" <?= $current_page <= 1 ? 'aria-disabled="true"' : '' ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    Anterior
                </a>
                <div class="pagination-pages">
                    <?php
                    $startPage = max(1, $current_page - 2);
                    $endPage = min($total_pages, $current_page + 2);
                    if ($startPage > 1): ?>
                        <a href="<?= $baseUrl ?>?page=1&per_page=<?= $per_page ?>&order=<?= $order ?>" class="pagination-page">1</a>
                        <?php if ($startPage > 2): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="<?= $baseUrl ?>?page=<?= $i ?>&per_page=<?= $per_page ?>&order=<?= $order ?>" class="pagination-page <?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($endPage < $total_pages): ?>
                        <?php if ($endPage < $total_pages - 1): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
                        <a href="<?= $baseUrl ?>?page=<?= $total_pages ?>&per_page=<?= $per_page ?>&order=<?= $order ?>" class="pagination-page"><?= $total_pages ?></a>
                    <?php endif; ?>
                </div>
                <a href="<?= $baseUrl ?>?page=<?= min($total_pages, $current_page + 1) ?>&per_page=<?= $per_page ?>&order=<?= $order ?>" class="pagination-btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>" <?= $current_page >= $total_pages ? 'aria-disabled="true"' : '' ?>>
                    Siguiente
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
        </div>
        <?php elseif ($total_records > 0): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando <strong>1</strong> - <strong><?= min($per_page, $total_records) ?></strong> de <strong><?= number_format($total_records) ?></strong> registros
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $title ?? 'Actividad de usuario';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/user_activity.css'];
$additional_js = ['/assets/js/admin/user_activity.js'];

require base_path('views/layouts/main.php');
?>
