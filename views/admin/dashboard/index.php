<?php
/**
 * GAC - Vista del Dashboard Principal
 * Diseño adaptado del sistema original
 * Filtra cards según role_views del usuario actual
 */
$userViews = user_role_views();
$can = function ($key) use ($userViews) {
    return $userViews === null || user_can_view($key, $userViews);
};
$content = ob_start();
?>

<div class="dashboard-container">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <svg class="dashboard-title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Dashboard
        </h1>
    </div>

    <!-- Cards de Acciones (Rojos) -->
    <div class="dashboard-actions">
        <div class="action-cards-grid">
            <?php if ($can('listar_correos')): ?>
            <!-- Lista de cuentas -->
            <a href="/admin/email-accounts" class="action-card">
                <div class="action-card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div class="action-card-text">Lista de cuentas</div>
            </a>
            <?php endif; ?>
            <?php if ($can('registro_acceso')): ?>
            <!-- Registro de Accesos -->
            <a href="/admin/user-access" class="action-card">
                <div class="action-card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div class="action-card-text">Registro de accesos</div>
            </a>
            <?php endif; ?>
            <?php if ($can('registro_masivo')): ?>
            <!-- Registro Masivo -->
            <a href="/admin/email-accounts/bulk-register" class="action-card">
                <div class="action-card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="action-card-text">Registro masivo</div>
            </a>
            <?php endif; ?>
            <?php if ($can('registro_asuntos')): ?>
            <!-- Registro de Asuntos -->
            <a href="/admin/email-subjects" class="action-card">
                <div class="action-card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div class="action-card-text">Registro de Asuntos</div>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cards de Estadísticas (Grises) -->
    <div class="dashboard-stats">
        <div class="stats-cards-grid">
            <?php if ($can('listar_correos') || $can('listar_gmail') || $can('listar_outlook') || $can('listar_pocoyoni')): ?>
            <!-- Correos Registrados (una sola card: Todo / Gmail / Outlook / Pocoyoni en la vista) -->
            <a href="/admin/email-accounts" class="stat-card stat-card-grey stat-card-link stat-card-email" title="Ver correos registrados">
                <div class="stat-card-icon stat-card-icon--correos">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value"><?= number_format(($stats['gmail_count'] ?? 0) + ($stats['outlook_count'] ?? 0) + ($stats['pocoyoni_count'] ?? 0)) ?></div>
                    <div class="stat-card-label">Correos Registrados</div>
                </div>
                <div class="stat-card-arrow">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </div>
            </a>
            <?php endif; ?>

            <?php if (function_exists('is_superadmin') && is_superadmin()): ?>
            <!-- Actividad de administrador -->
            <a href="/admin/user-activity" class="stat-card stat-card-grey stat-card-link" title="Actividad de administrador">
                <div class="stat-card-icon stat-card-icon--activity">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-label">Actividad de administrador</div>
                </div>
                <div class="stat-card-arrow">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </div>
            </a>
            <?php endif; ?>

            <?php if (function_exists('is_superadmin') && is_superadmin()): ?>
            <!-- ANÁLISIS (placeholder) -->
            <a href="#" class="stat-card stat-card-grey stat-card-link stat-card-analysis" title="Análisis">
                <div class="stat-card-icon stat-card-icon--analysis">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-label">ANÁLISIS</div>
                </div>
                <div class="stat-card-arrow">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($can('plataformas_activas')): ?>
            <!-- Plataformas Activas -->
            <a href="/admin/platforms" class="stat-card stat-card-grey stat-card-link" title="Ver plataformas activas">
                <div class="stat-card-icon stat-icon-yellow">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value"><?= number_format($stats['platforms_active']) ?></div>
                    <div class="stat-card-label">Plataformas activas</div>
                </div>
                <div class="stat-card-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Dashboard';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/dashboard.css'];
$additional_js = ['/assets/js/admin/dashboard.js'];

require base_path('views/layouts/main.php');
?>
