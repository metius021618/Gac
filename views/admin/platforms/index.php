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
        <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
            <button type="button" id="btnAddPlatform" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Agregar Plataforma
            </button>
        </div>
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

<!-- Modal Agregar Plataforma -->
<div id="addPlatformModal" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:2rem; width:90%; max-width:450px; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0; font-size:1.25rem;">Agregar Plataforma</h2>
            <button type="button" id="closeModal" style="background:none; border:none; cursor:pointer; color:#888; font-size:1.5rem; line-height:1;">&times;</button>
        </div>
        <form id="addPlatformForm" novalidate>
            <div class="form-group" style="margin-bottom:1rem;">
                <label for="platName" class="form-label">Nombre</label>
                <input type="text" id="platName" class="form-input" placeholder="Ej: Netflix" required>
                <span class="form-error" id="platNameError"></span>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label for="platSlug" class="form-label">Slug</label>
                <input type="text" id="platSlug" class="form-input" placeholder="Ej: netflix" required>
                <span class="form-error" id="platSlugError"></span>
                <small style="color:#888;">Identificador único (minúsculas, sin espacios)</small>
            </div>
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label for="platEnabled" class="form-label">Estado</label>
                <select id="platEnabled" class="form-select" required>
                    <option value="1" selected>Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" id="cancelModal" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Estilos toggle switch -->
<style>
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    cursor: pointer;
}
.toggle-switch .toggle-input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-switch .toggle-slider {
    position: absolute;
    inset: 0;
    background-color: #dc3545;
    border-radius: 24px;
    transition: background-color 0.25s ease;
}
.toggle-switch .toggle-slider::before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.25s ease;
}
.toggle-switch .toggle-input:checked + .toggle-slider {
    background-color: #28a745;
}
.toggle-switch .toggle-input:checked + .toggle-slider::before {
    transform: translateX(20px);
}
</style>

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
