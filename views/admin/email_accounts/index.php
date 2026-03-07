<?php
/**
 * GAC - Lista de cuentas (gestión completa: correo, usuario, plataforma, actividad, administrador, acciones)
 */

$content = ob_start();
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
           data-export-base="/admin/email-accounts/export-lista-excel"
           data-platform-id="<?= (int)($platform_id_filter ?? 0) ?>"
           data-activity-date="<?= htmlspecialchars($activity_date_filter ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                <div id="emailFiltersBar" class="email-filters-bar" style="display: none;">
                    <select id="filterPlatform" class="form-select email-filter-select" title="Filtrar por plataforma">
                        <option value="">Plataforma</option>
                    </select>
                    <input type="date" id="filterActivityDate" class="form-input email-filter-date" title="Filtrar por fecha de actividad" placeholder="Fecha">
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

<?php
$content = ob_get_clean();

$title = $title ?? 'Lista de cuentas';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css'];
$additional_js = ['/assets/js/admin/search-ajax.js', '/assets/js/admin/email_accounts.js'];

require base_path('views/layouts/main.php');
?>
