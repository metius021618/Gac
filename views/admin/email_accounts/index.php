<?php
/**
 * GAC - Vista de Listado de Cuentas de Email
 * Con búsqueda, paginación y diseño mejorado
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header admin-header--with-excel">
        <h1 class="admin-title">Correos Registrados</h1>
        <div class="admin-header-actions">
            <div class="excel-export-wrap">
                <button type="button" class="btn btn-excel" id="excelExportTrigger" title="Exportar a Excel">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <path d="M8 13h2"></path><path d="M8 17h2"></path>
                        <path d="M14 13h2"></path><path d="M14 17h2"></path>
                    </svg>
                    Excel
                </button>
            </div>
        </div>
    </div>
    <!-- Deslizador Todo / Gmail / Outlook / Pocoyoni (solo en vista principal) -->
    <?php $current_filter = $filter ?? ''; ?>
    <div class="correos-filter-slider">
        <a href="/admin/email-accounts" class="correos-filter-pill <?= $current_filter === '' ? 'active' : '' ?>">Todo</a>
        <a href="/admin/email-accounts?filter=gmail" class="correos-filter-pill <?= $current_filter === 'gmail' ? 'active' : '' ?>">Gmail</a>
        <a href="/admin/email-accounts?filter=outlook" class="correos-filter-pill <?= $current_filter === 'outlook' ? 'active' : '' ?>">Outlook</a>
        <a href="/admin/email-accounts?filter=pocoyoni" class="correos-filter-pill <?= $current_filter === 'pocoyoni' ? 'active' : '' ?>">Pocoyoni</a>
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

    <div class="admin-content">
        <!-- Barra de búsqueda y filtros -->
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

        <!-- Tabla de cuentas -->
        <?php require base_path('views/admin/email_accounts/_table.php'); ?>
    </div>
</div>

<!-- Modal exportar Excel: elegir Todo / Gmail / Outlook / Pocoyoni -->
<div id="excelExportModal" class="modal hidden" aria-hidden="true">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Exportar a Excel</h2>
            <button type="button" class="modal-close" id="closeExcelExportModal" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-content excel-export-cards">
            <a href="/admin/email-accounts/export-excel" class="excel-export-card" data-filter="">Todos</a>
            <a href="/admin/email-accounts/export-excel?filter=gmail" class="excel-export-card" data-filter="gmail">Gmail</a>
            <a href="/admin/email-accounts/export-excel?filter=outlook" class="excel-export-card" data-filter="outlook">Outlook</a>
            <a href="/admin/email-accounts/export-excel?filter=pocoyoni" class="excel-export-card" data-filter="pocoyoni">Pocoyoni</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Correos Registrados';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css'];
$additional_js = ['/assets/js/admin/search-ajax.js', '/assets/js/admin/email_accounts.js'];

require base_path('views/layouts/main.php');
?>
