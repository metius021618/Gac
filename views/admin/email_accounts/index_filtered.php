<?php
/**
 * GAC - Vista filtrada de correos (Gmail, Outlook, Pocoyoni)
 * Solo muestra: ID, Correo, Fecha registro + buscador
 */

$content = ob_start();
?>

<?php $current_filter = $filter ?? ''; ?>
<div class="admin-container">
    <div class="admin-header admin-header--with-excel">
        <h1 class="admin-title">Correos Registrados</h1>
        <div class="admin-header-actions">
            <div class="excel-export-wrap">
                <button type="button" class="btn btn-excel" id="excelExportTrigger" title="Exportar a Excel" data-filter="<?= htmlspecialchars($current_filter) ?>">
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
    <div class="correos-filter-slider">
        <a href="/admin/email-accounts" class="correos-filter-pill <?= $current_filter === '' ? 'active' : '' ?>">Todo</a>
        <a href="/admin/email-accounts?filter=gmail" class="correos-filter-pill <?= $current_filter === 'gmail' ? 'active' : '' ?>">Gmail</a>
        <a href="/admin/email-accounts?filter=outlook" class="correos-filter-pill <?= $current_filter === 'outlook' ? 'active' : '' ?>">Outlook</a>
        <a href="/admin/email-accounts?filter=pocoyoni" class="correos-filter-pill <?= $current_filter === 'pocoyoni' ? 'active' : '' ?>">Pocoyoni</a>
    </div>

    <?php if (!empty($_SESSION['gmail_success']) && ($filter ?? '') === 'gmail'): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($_SESSION['gmail_success']) ?>
            <?php unset($_SESSION['gmail_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['gmail_error']) && ($filter ?? '') === 'gmail'): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['gmail_error']) ?>
            <?php unset($_SESSION['gmail_error']); ?>
        </div>
    <?php endif; ?>

    <div class="admin-content">
        <div class="table-controls">
            <div class="search-input-wrapper">
                <span class="search-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" id="searchInput" class="search-input" placeholder="Buscar correo..." value="<?= htmlspecialchars($search_query ?? '') ?>">
                <?php if (!empty($search_query)): ?>
                    <button type="button" id="clearSearch" class="clear-search-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
            <div class="per-page-selector">
                <label for="perPage">Mostrar:</label>
                <select id="perPage" class="form-select">
                    <?php foreach ($valid_per_page ?? [15, 30, 60, 100, 0] as $option): ?>
                        <option value="<?= $option ?>" <?= ($per_page ?? 15) == $option ? 'selected' : '' ?>>
                            <?= $option == 0 ? 'Todos' : $option ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php require base_path('views/admin/email_accounts/_table_simple.php'); ?>
    </div>
</div>

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

$title = $title ?? 'Correos';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css'];
$additional_js = ['/assets/js/admin/search-ajax.js', '/assets/js/admin/email_accounts_filtered.js'];

require base_path('views/layouts/main.php');
?>
