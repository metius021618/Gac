<?php
/**
 * GAC - Vista previa del panel según vistas permitidas (iframe en Personalización de roles)
 * Alineada con el dashboard actual. No se muestran Análisis ni Actividad de administrador (solo superadmin).
 * En la preview no se muestra opción de Configuración (solo superadmin la tiene).
 */
$allowed_views = $allowed_views ?? [];
$allowed_keys = array_column($allowed_views, 'key');
$in = function ($key) use ($allowed_keys) { return in_array($key, $allowed_keys, true); };
$hasCorreos = $in('listar_correos') || $in('listar_gmail') || $in('listar_outlook') || $in('listar_pocoyoni');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista previa - <?= gac_name() ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin/main.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <img src="/assets/images/logocamb.png" alt="<?= gac_name() ?>" class="logo">
                </div>
                <nav class="main-nav">
                    <?php if ($in('dashboard') || !empty($allowed_keys)): ?>
                        <a href="#" class="nav-link" onclick="return false;">Dashboard</a>
                    <?php endif; ?>
                    <?php if ($hasCorreos): ?>
                        <a href="#" class="nav-link" onclick="return false;">Lista de cuentas</a>
                    <?php endif; ?>
                    <?php if ($in('registro_acceso')): ?>
                        <a href="#" class="nav-link" onclick="return false;">Registro de acceso</a>
                    <?php endif; ?>
                    <?php if ($in('registro_masivo')): ?>
                        <a href="#" class="nav-link" onclick="return false;">Registro masivo</a>
                    <?php endif; ?>
                    <?php if ($in('usuarios') || $in('administradores')): ?>
                        <a href="#" class="nav-link" onclick="return false;">Revendedores</a>
                    <?php endif; ?>
                </nav>
                <div class="user-menu-container">
                    <span class="user-welcome">Vista previa</span>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <svg class="dashboard-title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Dashboard
                </h1>
            </div>

            <!-- Cards de Acciones (igual que dashboard real) -->
            <div class="dashboard-actions">
                <div class="action-cards-grid">
                    <?php if ($in('listar_correos')): ?>
                    <span class="action-card" style="pointer-events: none; cursor: default;">
                        <div class="action-card-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div class="action-card-text">Lista de cuentas</div>
                    </span>
                    <?php endif; ?>
                    <?php if ($in('registro_acceso')): ?>
                    <span class="action-card" style="pointer-events: none; cursor: default;">
                        <div class="action-card-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <div class="action-card-text">Registro de accesos</div>
                    </span>
                    <?php endif; ?>
                    <?php if ($in('registro_masivo')): ?>
                    <span class="action-card" style="pointer-events: none; cursor: default;">
                        <div class="action-card-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="action-card-text">Registro masivo</div>
                    </span>
                    <?php endif; ?>
                    <?php if ($in('registro_asuntos')): ?>
                    <span class="action-card" style="pointer-events: none; cursor: default;">
                        <div class="action-card-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <div class="action-card-text">Asuntos</div>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cards de Estadísticas (alineado con dashboard actual: Correos Registrados, Plataformas, Administradores; sin Análisis ni Actividad de administrador) -->
            <div class="dashboard-stats">
                <div class="stats-cards-grid">
                    <?php if ($hasCorreos): ?>
                    <span class="stat-card stat-card-grey stat-card-email stat-card-link" style="pointer-events: none; cursor: default;">
                        <div class="stat-card-icon stat-card-icon--correos">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div class="stat-card-content stat-card-content--value">
                            <span class="stat-card-value">—</span>
                            <span class="stat-card-label">Correos Registrados</span>
                        </div>
                        <div class="stat-card-arrow">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </div>
                    </span>
                    <?php endif; ?>

                    <?php if ($in('plataformas_activas')): ?>
                    <span class="stat-card stat-card-grey" style="pointer-events: none; cursor: default;">
                        <div class="stat-card-icon stat-icon-yellow">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        </div>
                        <div class="stat-card-content stat-card-content--value">
                            <span class="stat-card-value">—</span>
                            <span class="stat-card-label">Plataformas activas</span>
                        </div>
                        <div class="stat-card-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </span>
                    <?php endif; ?>

                    <?php if ($in('usuarios') || $in('administradores')): ?>
                    <span class="stat-card stat-card-grey" style="pointer-events: none; cursor: default;">
                        <div class="stat-card-icon stat-icon-green">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-value">—</div>
                            <div class="stat-card-label">Revendedores</div>
                        </div>
                        <div class="stat-card-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($allowed_keys)): ?>
            <p style="color: var(--text-muted); padding: 2rem; text-align: center;">Selecciona vistas en el panel para ver la previsualización.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
