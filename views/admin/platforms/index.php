<?php
/**
 * GAC - Vista de Lista de Plataformas Activas
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Plataformas Activas</h1>
        <p class="admin-subtitle">Gestiona las plataformas disponibles en el sistema</p>
    </div>

    <div class="admin-content">
        <div class="table-controls">
            <div class="search-input-wrapper">
                <span class="search-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" id="searchInput" class="search-input" placeholder="Buscar por nombre o slug..." value="<?= htmlspecialchars($search_query) ?>">
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

        <!-- Tabla de plataformas -->
        <?php require base_path('views/admin/platforms/_table.php'); ?>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Plataformas Activas';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css'];
$additional_js = ['/assets/js/admin/search-ajax.js', '/assets/js/admin/platforms.js'];

require base_path('views/layouts/main.php');
?>
