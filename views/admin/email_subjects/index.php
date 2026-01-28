<?php
/**
 * GAC - Vista de Lista de Asuntos de Email
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            Asuntos de correo
        </h1>
    </div>

    <div class="admin-content">
        <div class="table-controls">
            <div class="table-controls-left">
                <a href="/admin/email-subjects/create" class="btn btn-primary btn-new-subject">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nuevo asunto
                </a>
            </div>
            
            <div class="table-controls-right">
                <div class="search-input-wrapper">
                    <span class="search-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input" 
                        placeholder="Buscar por plataforma o asunto..." 
                        value="<?= htmlspecialchars($search_query ?? '') ?>"
                        autocomplete="off"
                    >
                    <button type="button" id="clearSearch" class="clear-search-btn" style="display: <?= !empty($search_query) ? 'flex' : 'none' ?>;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                
                <div class="per-page-selector">
                    <label for="perPageSelect" class="per-page-label">Mostrar:</label>
                    <select id="perPageSelect" class="form-select">
                        <?php 
                        $validPerPage = $valid_per_page ?? [10, 15, 30, 60, 100, 0];
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

        <!-- Tabla de asuntos -->
        <?php require base_path('views/admin/email_subjects/_table.php'); ?>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Asuntos de correo';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_subjects.css'];
$additional_js = ['/assets/js/admin/search-ajax.js', '/assets/js/admin/email_subjects.js'];

require base_path('views/layouts/main.php');
?>
