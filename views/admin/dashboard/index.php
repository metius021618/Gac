<?php
/**
 * GAC - Vista del Dashboard Principal
 * Diseño adaptado del sistema original
 */

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

            <!-- Registro de Accesos -->
            <a href="/admin/codes" class="action-card">
                <div class="action-card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div class="action-card-text">Registro de accesos</div>
            </a>

            <!-- Registro Masiva -->
            <a href="/admin/users" class="action-card">
                <div class="action-card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="action-card-text">Registro masiva</div>
            </a>

            <!-- Registro de Asuntos -->
            <a href="/admin/settings" class="action-card">
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
        </div>
    </div>

    <!-- Cards de Estadísticas (Grises) -->
    <div class="dashboard-stats">
        <div class="stats-cards-grid">
            <!-- Correos Registrados -->
            <a href="/admin/email-accounts" class="stat-card stat-card-grey stat-card-link" title="Ver correos registrados">
                <div class="stat-card-icon stat-icon-blue">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                    </svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-value"><?= number_format($stats['email_accounts']) ?></div>
                    <div class="stat-card-label">Correos registrados</div>
                </div>
                <div class="stat-card-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </a>

            <!-- Plataformas Activas -->
            <div class="stat-card stat-card-grey">
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
            </div>

            <!-- Administradores -->
            <div class="stat-card stat-card-grey">
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
            </div>
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
