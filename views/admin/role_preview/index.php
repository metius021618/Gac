<?php
/**
 * GAC - Vista previa del panel según vistas permitidas (iframe en Personalización de roles)
 * Renderiza la misma apariencia que el panel real: header, nav filtrado y contenido tipo dashboard
 */
$allowed_views = $allowed_views ?? [];
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
                    <?php foreach ($allowed_views as $v): ?>
                        <a href="<?= htmlspecialchars($v['url']) ?>" class="nav-link" onclick="return false;"><?= htmlspecialchars($v['nav_label']) ?></a>
                    <?php endforeach; ?>
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

            <div class="dashboard-actions">
                <div class="action-cards-grid">
                    <?php foreach ($allowed_views as $v): ?>
                        <span class="action-card" style="pointer-events: none; cursor: default;">
                            <div class="action-card-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                            </div>
                            <div class="action-card-text"><?= htmlspecialchars($v['label']) ?></div>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($allowed_views)): ?>
            <div class="dashboard-stats">
                <div class="stats-cards-grid">
                    <?php foreach ($allowed_views as $v): ?>
                        <span class="stat-card stat-card-grey" style="pointer-events: none; cursor: default;">
                            <div class="stat-card-icon stat-icon-yellow">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                            </div>
                            <div class="stat-card-content">
                                <div class="stat-card-value">—</div>
                                <div class="stat-card-label"><?= htmlspecialchars($v['label']) ?></div>
                            </div>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($allowed_views)): ?>
            <p style="color: var(--text-muted); padding: 2rem; text-align: center;">Selecciona vistas en el panel para ver la previsualización.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
