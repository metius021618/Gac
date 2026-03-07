<?php
/**
 * GAC - Lista de cuentas (gestión completa: correo, usuario, plataforma, actividad, administrador, acciones)
 */

$content = ob_start();
$filter_date_from = $filter_date_from ?? '';
$filter_date_to = $filter_date_to ?? '';
$filter_time_range = $filter_time_range ?? '';
$baseUrlLista = '/admin/email-accounts';
$queryParamsLista = function($overrides = []) use ($baseUrlLista, $current_page, $per_page, $search_query, $platform_id_filter, $filter_date_from, $filter_date_to, $filter_time_range) {
    $p = array_merge([
        'page' => $current_page ?? 1,
        'per_page' => $per_page ?? 15,
        'search' => $search_query ?? '',
        'platform_id' => $platform_id_filter ?? '',
        'date_from' => $filter_date_from,
        'date_to' => $filter_date_to,
        'time_range' => $filter_time_range,
    ], $overrides);
    $p = array_filter($p, function($v) { return $v !== '' && $v !== null; });
    return $baseUrlLista . '?' . http_build_query($p);
};
$timeRangeLabel = 'Todo';
if ($filter_time_range === '7') $timeRangeLabel = 'Últimos 7 días';
elseif ($filter_time_range === '30') $timeRangeLabel = 'Últimos 30 días';
elseif ($filter_time_range === '90') $timeRangeLabel = 'Últimos 90 días';
elseif ($filter_date_from && $filter_date_to) $timeRangeLabel = 'Personalizado';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Lista de cuentas</h1>
    </div>

    <?php if (!empty($_SESSION['gmail_success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($_SESSION['gmail_success']) ?>
            <?php unset($_SESSION['gmail_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['gmail_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['gmail_error']) ?>
            <?php unset($_SESSION['gmail_error']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['outlook_success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($_SESSION['outlook_success']) ?>
            <?php unset($_SESSION['outlook_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['outlook_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['outlook_error']) ?>
            <?php unset($_SESSION['outlook_error']); ?>
        </div>
    <?php endif; ?>

    <div class="lista-cuentas-excel-bar">
        <a href="#" id="listaCuentasExcelBtn" class="btn btn-primary btn-excel-lista" title="Exportar a Excel (lo que se muestra)"
           data-export-base="/admin/email-accounts/export-lista-excel">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <path d="M8 13h2"></path>
                <path d="M8 17h2"></path>
                <path d="M14 13h2"></path>
                <path d="M14 17h2"></path>
            </svg>
            Excel
        </a>
    </div>

    <div class="admin-content">
        <div class="table-controls">
            <div class="search-box">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="search-input" 
                    placeholder="Buscar por correo o usuario..." 
                    value="<?= htmlspecialchars($search_query ?? '') ?>"
                    autocomplete="off"
                >
                <button class="search-clear" id="clearSearch" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div class="table-controls-right">
                <div id="emailFiltersBar" class="email-filters-bar lista-cuentas-filters">
                    <?php
                    $platforms_list = $platforms_list ?? [];
                    $platform_id_filter = (int)($platform_id_filter ?? 0);
                    $plataformaFilterLabel = 'Todas';
                    foreach ($platforms_list as $p) {
                        if ((int)($p['id'] ?? 0) === $platform_id_filter) {
                            $plataformaFilterLabel = $p['display_name'] ?? $p['name'] ?? 'Todas';
                            break;
                        }
                    }
                    ?>
                    <div class="analisis-filter-dropdown" data-filter="plataforma" id="listaCuentasPlatformDropdown">
                        <span class="analisis-filter-label">Plataforma</span><span class="analisis-filter-sep"> - </span><span class="analisis-filter-value" id="listaCuentasPlatformValue"><?= htmlspecialchars($plataformaFilterLabel) ?></span>
                        <ul class="analisis-filter-menu">
                            <li><a href="<?= $queryParamsLista(['platform_id' => '', 'page' => 1]) ?>">Todas</a></li>
                            <?php foreach ($platforms_list as $p):
                                $pid = (int)($p['id'] ?? 0);
                                $pname = htmlspecialchars($p['display_name'] ?? $p['name'] ?? '', ENT_QUOTES, 'UTF-8');
                                if ($pname === '') continue;
                            ?>
                                <li><a href="<?= $queryParamsLista(['platform_id' => $pid, 'page' => 1]) ?>"><?= $pname ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="activity-filter-dropdown" data-filter="time" id="listaCuentasTimeFilterDropdown">
                        <span class="activity-filter-label">Tiempo</span><span class="activity-filter-sep"> - </span><span class="activity-filter-value" id="listaCuentasTimeFilterValue"><?= htmlspecialchars($timeRangeLabel) ?></span>
                        <ul class="activity-filter-menu">
                            <li><a href="<?= $queryParamsLista(['date_from' => '', 'date_to' => '', 'time_range' => '', 'page' => 1]) ?>">Todo</a></li>
                            <li><a href="<?= $queryParamsLista(['date_from' => date('Y-m-d', strtotime('-7 days')), 'date_to' => date('Y-m-d'), 'time_range' => '7', 'page' => 1]) ?>">Últimos 7 días</a></li>
                            <li><a href="<?= $queryParamsLista(['date_from' => date('Y-m-d', strtotime('-30 days')), 'date_to' => date('Y-m-d'), 'time_range' => '30', 'page' => 1]) ?>">Últimos 30 días</a></li>
                            <li><a href="<?= $queryParamsLista(['date_from' => date('Y-m-d', strtotime('-90 days')), 'date_to' => date('Y-m-d'), 'time_range' => '90', 'page' => 1]) ?>">Últimos 90 días</a></li>
                            <li><a href="#" id="listaCuentasTimeFilterCustom" class="activity-filter-custom-link">Personalizado</a></li>
                        </ul>
                    </div>
                </div>
                <?php if (function_exists('user_can_action') && user_can_action('listar_correos', 'eliminar')): ?>
                <button id="multiSelectBtn" class="btn btn-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    Selección Múltiple
                </button>
                
                <button id="bulkDeleteBtn" class="btn btn-danger" style="display: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Eliminar (<span id="selectedCount">0</span>)
                </button>
                <?php endif; ?>
                
                <div class="per-page-selector">
                    <label for="perPageSelect" class="per-page-label">Mostrar:</label>
                    <select id="perPageSelect" class="form-select">
                        <?php 
                        $validPerPage = $valid_per_page ?? [15, 30, 60, 100, 0];
                        $currentPerPage = $per_page ?? 15;
                        foreach ($validPerPage as $option): 
                            $optionValue = $option === 0 ? 'all' : $option;
                            $optionLabel = $option === 0 ? 'Todos' : $option;
                            $isSelected = ($currentPerPage == $option || ($option === 0 && ($currentPerPage === 'all' || $currentPerPage === 0)));
                        ?>
                            <option value="<?= $optionValue ?>" <?= $isSelected ? 'selected' : '' ?>>
                                <?= $optionLabel ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div id="emailAccountsTableWrapper">
            <?php require base_path('views/admin/email_accounts/_table.php'); ?>
        </div>
    </div>
</div>

<!-- Modal rango de tiempo (Lista de cuentas) - mismo diseño que Actividad de administrador -->
<div id="listaCuentasDateRangeModal" class="modal hidden" aria-hidden="true">
    <div class="modal-overlay"></div>
    <div class="modal-container activity-date-range-modal">
        <div class="modal-header activity-date-range-modal-header">
            <h2 class="modal-title">Selecciona un rango de tiempo</h2>
            <button type="button" class="modal-close modal-close--large" id="closeListaCuentasDateModal" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-content activity-date-range-fields">
            <div class="activity-date-field-group">
                <label class="activity-date-label">Hora de inicio</label>
                <div class="activity-date-input-wrap">
                    <input type="date" id="listaCuentasDateFrom" class="form-input activity-date-input">
                </div>
            </div>
            <span class="activity-date-sep">a</span>
            <div class="activity-date-field-group">
                <label class="activity-date-label">Hora de finalización</label>
                <div class="activity-date-input-wrap">
                    <input type="date" id="listaCuentasDateTo" class="form-input activity-date-input">
                </div>
            </div>
        </div>
        <div class="modal-footer activity-date-range-footer">
            <button type="button" class="btn btn-activity-date-continue" id="listaCuentasDateRangeApply">Continuar</button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Lista de cuentas';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css', '/assets/css/admin/user_activity.css', '/assets/css/admin/analisis.css'];
$additional_js = ['/assets/js/admin/search-ajax.js', '/assets/js/admin/email_accounts.js'];

require base_path('views/layouts/main.php');
?>
