<?php
/**
 * GAC - Vista Actividad de administrador (solo superadmin)
 * Tabla: USUARIO | ACCIÓN | DESCRIPCIÓN | FECHA | HORA
 * Filtros: Acción, Admin, Tiempo (hover). Per page 50-75-100-All. Export Excel.
 */

$content = ob_start();
$activities = $activities ?? [];
$total_records = (int)($total_records ?? 0);
$current_page = (int)($current_page ?? 1);
$per_page = (int)($per_page ?? 50);
$total_pages = (int)($total_pages ?? 1);
$order = ($order ?? 'desc') === 'asc' ? 'asc' : 'desc';
$valid_per_page = $valid_per_page ?? [50, 75, 100, 0];
$baseUrl = '/admin/user-activity';
$filter_action = $filter_action ?? '';
$filter_admin = $filter_admin ?? '';
$filter_date_from = $filter_date_from ?? '';
$filter_date_to = $filter_date_to ?? '';
$filter_time_range = $filter_time_range ?? '';
$usernames = $usernames ?? [];

$queryParams = function($overrides = []) use ($baseUrl, $current_page, $per_page, $order, $filter_action, $filter_admin, $filter_date_from, $filter_date_to, $filter_time_range) {
    $p = array_merge([
        'page' => $current_page,
        'per_page' => $per_page,
        'order' => $order,
        'action' => $filter_action,
        'admin' => $filter_admin,
        'date_from' => $filter_date_from,
        'date_to' => $filter_date_to,
        'time_range' => $filter_time_range,
    ], $overrides);
    $p = array_filter($p, function($v) { return $v !== '' && $v !== null; });
    return $baseUrl . '?' . http_build_query($p);
};
$exportHref = '/admin/user-activity/export-excel?' . http_build_query(array_filter([
    'action' => $filter_action,
    'admin' => $filter_admin,
    'date_from' => $filter_date_from,
    'date_to' => $filter_date_to,
], function($v) { return $v !== ''; }));

$timeRangeLabel = 'Todo';
if ($filter_time_range === '7') $timeRangeLabel = 'Últimos 7 días';
elseif ($filter_time_range === '30') $timeRangeLabel = 'Últimos 30 días';
elseif ($filter_time_range === '90') $timeRangeLabel = 'Últimos 90 días';
elseif ($filter_date_from && $filter_date_to) $timeRangeLabel = 'Personalizado';
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
            Actividad de administrador
        </h1>
    </div>

    <!-- Barra: Mostrar por página (fuera del div de controles) + Filtros -->
    <div class="user-activity-top-bar">
        <div class="user-activity-filters">
            <div class="activity-filter-dropdown" data-filter="action">
                <span class="activity-filter-label">Acción</span><span class="activity-filter-sep"> - </span><span class="activity-filter-value"><?= $filter_action ? htmlspecialchars(\Gac\Repositories\UserActivityLogRepository::actionLabel($filter_action)) : 'Todas' ?></span>
                <ul class="activity-filter-menu">
                    <li><a href="<?= $queryParams(['action' => '', 'page' => 1]) ?>">Todas</a></li>
                    <li><a href="<?= $queryParams(['action' => 'agregar_correo', 'page' => 1]) ?>">Agregar correo</a></li>
                    <li><a href="<?= $queryParams(['action' => 'edicion', 'page' => 1]) ?>">Edición</a></li>
                    <li><a href="<?= $queryParams(['action' => 'eliminar', 'page' => 1]) ?>">Eliminar</a></li>
                </ul>
            </div>
            <div class="activity-filter-dropdown" data-filter="admin">
                <span class="activity-filter-label">Usuario</span><span class="activity-filter-sep"> - </span><span class="activity-filter-value"><?= $filter_admin ? htmlspecialchars($filter_admin) : 'Todos' ?></span>
                <ul class="activity-filter-menu">
                    <li><a href="<?= $queryParams(['admin' => '', 'page' => 1]) ?>">Todos</a></li>
                    <?php foreach ($usernames as $u): ?>
                    <li><a href="<?= $queryParams(['admin' => $u, 'page' => 1]) ?>"><?= htmlspecialchars($u) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="activity-filter-dropdown" data-filter="time" id="timeFilterDropdown">
                <span class="activity-filter-label">Tiempo</span><span class="activity-filter-sep"> - </span><span class="activity-filter-value" id="timeFilterValue"><?= htmlspecialchars($timeRangeLabel) ?></span>
                <ul class="activity-filter-menu">
                    <li><a href="<?= $queryParams(['date_from' => '', 'date_to' => '', 'time_range' => '', 'page' => 1]) ?>">Todo</a></li>
                    <li><a href="<?= $queryParams(['date_from' => date('Y-m-d', strtotime('-7 days')), 'date_to' => date('Y-m-d'), 'time_range' => '7', 'page' => 1]) ?>">Últimos 7 días</a></li>
                    <li><a href="<?= $queryParams(['date_from' => date('Y-m-d', strtotime('-30 days')), 'date_to' => date('Y-m-d'), 'time_range' => '30', 'page' => 1]) ?>">Últimos 30 días</a></li>
                    <li><a href="<?= $queryParams(['date_from' => date('Y-m-d', strtotime('-90 days')), 'date_to' => date('Y-m-d'), 'time_range' => '90', 'page' => 1]) ?>">Últimos 90 días</a></li>
                    <li><a href="#" id="timeFilterCustom" class="activity-filter-custom-link">Personalizado</a></li>
                </ul>
            </div>
            <a href="<?= $baseUrl ?>" class="btn btn-secondary btn-reset-filters">Reiniciar</a>
        </div>
        <div class="user-activity-right">
            <a href="<?= htmlspecialchars($exportHref) ?>" class="btn btn-excel" title="Exportar a Excel (lo mostrado/filtrado)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M8 13h2"></path><path d="M8 17h2"></path><path d="M14 13h2"></path><path d="M14 17h2"></path></svg>
                Excel
            </a>
            <div class="per-page-selector per-page-selector--outside">
                <label for="perPageSelect" class="per-page-label">Mostrar:</label>
                <select id="perPageSelect" class="form-select user-activity-per-page">
                <?php foreach ($valid_per_page as $option): ?>
                    <option value="<?= $option ?>" <?= $per_page === $option ? 'selected' : '' ?>>
                        <?= $option === 0 ? 'All' : $option ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="admin-content">
        <div class="table-container">
            <table class="admin-table" id="userActivityTable">
                <thead>
                    <tr>
                        <th style="width: 14%;">USUARIO</th>
                        <th style="width: 14%;">ACCIÓN</th>
                        <th style="width: 44%;">DESCRIPCIÓN</th>
                        <th style="width: 14%;">
                            <a href="<?= $queryParams(['order' => $order === 'desc' ? 'asc' : 'desc']) ?>" class="sortable-header sortable-header--date">
                                FECHA
                                <?= $order === 'desc' ? '<span class="sort-icon">▼</span>' : '<span class="sort-icon">▲</span>' ?>
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
                                <td class="activity-description"><?= nl2br(htmlspecialchars($row['description'] ?? '')) ?></td>
                                <td><?= $datePart ?></td>
                                <td><?= $timePart ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php 
        $from = $per_page > 0 ? (($current_page - 1) * $per_page) + 1 : 1;
        $to = $per_page > 0 ? min($current_page * $per_page, $total_records) : $total_records;
        if ($total_records > 0): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando <strong><?= $from ?></strong> - <strong><?= $to ?></strong> de <strong><?= number_format($total_records) ?></strong> registros
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="pagination-controls">
                <a href="<?= $queryParams(['page' => max(1, $current_page - 1)]) ?>" class="pagination-btn <?= $current_page <= 1 ? 'disabled' : '' ?>" <?= $current_page <= 1 ? 'aria-disabled="true"' : '' ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    Anterior
                </a>
                <div class="pagination-pages">
                    <?php $startPage = max(1, $current_page - 2); $endPage = min($total_pages, $current_page + 2); ?>
                    <?php if ($startPage > 1): ?>
                        <a href="<?= $queryParams(['page' => 1]) ?>" class="pagination-page">1</a>
                        <?php if ($startPage > 2): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="<?= $queryParams(['page' => $i]) ?>" class="pagination-page <?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($endPage < $total_pages): ?>
                        <?php if ($endPage < $total_pages - 1): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
                        <a href="<?= $queryParams(['page' => $total_pages]) ?>" class="pagination-page"><?= $total_pages ?></a>
                    <?php endif; ?>
                </div>
                <a href="<?= $queryParams(['page' => min($total_pages, $current_page + 1)]) ?>" class="pagination-btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>" <?= $current_page >= $total_pages ? 'aria-disabled="true"' : '' ?>>
                    Siguiente
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal rango de tiempo (solo fecha) - diseño como imagen referencia -->
<div id="activityDateRangeModal" class="modal hidden" aria-hidden="true">
    <div class="modal-overlay"></div>
    <div class="modal-container activity-date-range-modal">
        <div class="modal-header activity-date-range-modal-header">
            <h2 class="modal-title">Selecciona un rango de tiempo</h2>
            <button type="button" class="modal-close modal-close--large" id="closeActivityDateModal" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-content activity-date-range-fields">
            <div class="activity-date-field-group">
                <label class="activity-date-label">Hora de inicio</label>
                <div class="activity-date-input-wrap">
                    <input type="date" id="activityDateFrom" class="form-input activity-date-input">
                </div>
            </div>
            <span class="activity-date-sep">a</span>
            <div class="activity-date-field-group">
                <label class="activity-date-label">Hora de finalización</label>
                <div class="activity-date-input-wrap">
                    <input type="date" id="activityDateTo" class="form-input activity-date-input">
                </div>
            </div>
        </div>
        <div class="modal-footer activity-date-range-footer">
            <button type="button" class="btn btn-activity-date-continue" id="activityDateRangeApply">Continuar</button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $title ?? 'Actividad de administrador';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/user_activity.css'];
$additional_js = ['/assets/js/admin/user_activity.js'];

require base_path('views/layouts/main.php');
?>
