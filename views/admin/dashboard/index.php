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
            <!-- Listar Correos -->
            <a href="/admin/email-accounts" class="action-card">
                <div class="action-card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div class="action-card-text">Listar correos</div>
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
            <?php if ($can('listar_gmail') || $can('listar_outlook') || $can('listar_pocoyoni')): ?>
            <div class="stats-emails-wrapper">
                <?php if ($can('listar_gmail')): ?>
                <a href="/admin/email-accounts?filter=gmail" class="stat-card stat-card-grey stat-card-link stat-card-email" title="Ver correos Gmail">
                    <div class="stat-card-icon" style="background: rgba(234,67,53,0.1);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="#EA4335">
                            <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.636H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L12 9.313l8.073-5.82C21.69 2.28 24 3.434 24 5.457z"/>
                        </svg>
                    </div>
                    <div class="stat-card-content">
                        <div class="stat-card-value"><?= number_format($stats['gmail_count'] ?? 0) ?></div>
                        <div class="stat-card-label">Gmail</div>
                    </div>
                    <div class="stat-card-arrow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </a>
                <?php endif; ?>
                <?php if ($can('listar_outlook')): ?>
                <a href="/admin/email-accounts?filter=outlook" class="stat-card stat-card-grey stat-card-link stat-card-email" title="Ver correos Outlook">
                    <div class="stat-card-icon" style="background: rgba(0,120,212,0.1);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="#0078D4">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                    <div class="stat-card-content">
                        <div class="stat-card-value"><?= number_format($stats['outlook_count'] ?? 0) ?></div>
                        <div class="stat-card-label">Outlook</div>
                    </div>
                    <div class="stat-card-arrow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </a>
                <?php endif; ?>
                <?php if ($can('listar_pocoyoni')): ?>
                <a href="/admin/email-accounts?filter=pocoyoni" class="stat-card stat-card-grey stat-card-link stat-card-email" title="Ver correos Pocoyoni">
                    <div class="stat-card-icon" style="background: rgba(255,193,7,0.15);">
                        <div style="width: 28px; height: 28px; background: #FFC107; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #333; font-size: 13px;">P</div>
                    </div>
                    <div class="stat-card-content">
                        <div class="stat-card-value"><?= number_format($stats['pocoyoni_count'] ?? 0) ?></div>
                        <div class="stat-card-label">Pocoyoni</div>
                    </div>
                    <div class="stat-card-arrow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </a>
                <?php endif; ?>
            </div>
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

            <?php if ($can('administradores')): ?>
            <!-- Administradores -->
            <a href="/admin/administrators" class="stat-card stat-card-grey stat-card-link" title="Ver administradores">
                <div class="stat-card-icon stat-icon-green">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value"><?= number_format($stats['administrators']) ?></div>
                    <div class="stat-card-label">Administradores</div>
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
