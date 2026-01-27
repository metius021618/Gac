<?php
/**
 * GAC - Vista de Listado de Cuentas de Email
 * Con búsqueda, paginación y diseño mejorado
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Correos Registrados</h1>
        <a href="/admin/email-accounts/create" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Agregar Cuenta
        </a>
    </div>

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
                    value="<?= htmlspecialchars($search ?? '') ?>"
                    autocomplete="off"
                >
                <button class="search-clear" id="clearSearch" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div class="per-page-selector">
                <label for="perPageSelect" class="per-page-label">Mostrar:</label>
                <select id="perPageSelect" class="form-select">
                    <?php 
                    $validPerPage = [15, 30, 60, 100, 0];
                    $currentPerPage = $per_page ?? 15;
                    foreach ($validPerPage as $option): 
                        $optionValue = $option === 0 ? 'all' : $option;
                        $optionLabel = $option === 0 ? 'Todos' : $option;
                    ?>
                        <option value="<?= $optionValue ?>" <?= ($currentPerPage == $option || ($option === 0 && $currentPerPage === 'all')) ? 'selected' : '' ?>>
                            <?= $optionLabel ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Tabla de cuentas -->
        <?php require base_path('views/admin/email_accounts/_table.php'); ?>
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
$additional_js = ['/assets/js/admin/email_accounts.js'];

require base_path('views/layouts/main.php');
?>
