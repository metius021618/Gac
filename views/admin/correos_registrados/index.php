<?php
/**
 * GAC - Correos registrados: solo ID, Correo, Asignado, Acciones (eliminar).
 * Filtro Todo / Gmail / Outlook / Pocoyoni. Export Excel.
 */
$content = ob_start();
$current_filter = $filter ?? '';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Correos Registrados</h1>
    </div>
    <div class="correos-filter-row">
        <div class="correos-filter-slider">
            <button type="button" class="correos-filter-pill <?= $current_filter === '' ? 'active' : '' ?>" data-filter="">Todo</button>
            <button type="button" class="correos-filter-pill <?= $current_filter === 'gmail' ? 'active' : '' ?>" data-filter="gmail">Gmail</button>
            <button type="button" class="correos-filter-pill <?= $current_filter === 'outlook' ? 'active' : '' ?>" data-filter="outlook">Outlook</button>
            <button type="button" class="correos-filter-pill <?= $current_filter === 'pocoyoni' ? 'active' : '' ?>" data-filter="pocoyoni">Pocoyoni</button>
        </div>
        <div class="correos-filter-excel">
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

    <div class="admin-content">
        <div class="table-controls">
            <div class="search-box">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" id="searchInput" class="search-input" placeholder="Buscar por correo..."
                       value="<?= htmlspecialchars($search_query ?? '') ?>" autocomplete="off">
                <button class="search-clear" id="clearSearch" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="table-controls-right">
                <div class="per-page-selector">
                    <label for="perPageSelect" class="per-page-label">Mostrar:</label>
                    <select id="perPageSelect" class="form-select">
                        <option value="15" <?= ($per_page ?? 15) == 15 ? 'selected' : '' ?>>15</option>
                        <option value="30" <?= ($per_page ?? 15) == 30 ? 'selected' : '' ?>>30</option>
                        <option value="60" <?= ($per_page ?? 15) == 60 ? 'selected' : '' ?>>60</option>
                        <option value="100" <?= ($per_page ?? 15) == 100 ? 'selected' : '' ?>>100</option>
                        <option value="all" <?= ($per_page ?? 15) == 0 ? 'selected' : '' ?>>Todos</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="correosRegistradosTableWrapper">
            <?php require base_path('views/admin/correos_registrados/_table.php'); ?>
        </div>
    </div>
</div>

<!-- Modal exportar Excel -->
<div id="excelExportModal" class="modal hidden" aria-hidden="true">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Exportar a Excel</h2>
            <button type="button" class="modal-close" id="closeExcelExportModal" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-content excel-export-cards">
            <a href="/admin/correos-registrados/export-excel" class="excel-export-card" data-filter="">
                <span class="excel-export-card-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </span>
                Todos
            </a>
            <a href="/admin/correos-registrados/export-excel?filter=gmail" class="excel-export-card" data-filter="gmail">
                <span class="excel-export-card-icon excel-export-card-icon--gmail" aria-hidden="true">
                    <!-- Icono Gmail (sobre rojo característico) -->
                    <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#EA4335" d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/><path fill="#fff" fill-opacity=".95" d="M12 11l8-5H4l8 5z"/></svg>
                </span>
                Gmail
            </a>
            <a href="/admin/correos-registrados/export-excel?filter=outlook" class="excel-export-card" data-filter="outlook">
                <span class="excel-export-card-icon excel-export-card-icon--outlook" aria-hidden="true">
                    <!-- Icono Outlook (azul Microsoft) -->
                    <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#0078D4" d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/><path fill="#28A8EA" d="M12 11l8-5H4l8 5z"/></svg>
                </span>
                Outlook
            </a>
            <a href="/admin/correos-registrados/export-excel?filter=pocoyoni" class="excel-export-card" data-filter="pocoyoni">
                <span class="excel-export-card-icon excel-export-card-icon--pocoyoni" aria-hidden="true">
                    <!-- P amarilla Pocoyoni -->
                    <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><text x="12" y="18" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="#F5D000">P</text></svg>
                </span>
                Pocoyoni
            </a>
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
$additional_js = ['/assets/js/admin/search-ajax.js', '/assets/js/admin/correos_registrados.js'];

require base_path('views/layouts/main.php');
?>
