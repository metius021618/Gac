<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($title) ? htmlspecialchars($title) . ' - ' : '' ?><?= gac_name() ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/logo.png">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/components/modal.css">
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?= isset($description) ? htmlspecialchars($description) : 'Sistema de consulta de códigos de acceso' ?>">
    <meta name="author" content="<?= gac_name() ?>">
    
    <!-- Preconnect para recursos externos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <img src="/assets/images/logo.png" alt="<?= gac_name() ?>" class="logo">
                </div>
                <?php if (isset($show_nav) && $show_nav): ?>
                <nav class="main-nav">
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <a href="/admin/dashboard" class="nav-link">Dashboard</a>
                        <a href="/admin/email-accounts" class="nav-link">Correos</a>
                        <a href="/admin/codes" class="nav-link">Códigos</a>
                        <a href="/admin/users" class="nav-link">Usuarios</a>
                    <?php else: ?>
                        <a href="/" class="nav-link">Inicio</a>
                        <a href="/login" class="nav-link">Login</a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
                
                <?php if (isset($show_nav) && $show_nav && isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                <div class="user-menu-container">
                    <button class="user-menu-trigger" id="userMenuTrigger">
                        <span class="user-welcome">Bienvenido, <?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></span>
                        <svg class="user-menu-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="user-menu-dropdown hidden" id="userMenuDropdown">
                        <a href="/admin/settings" class="user-menu-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3m15.364 6.364l-4.243-4.243m-4.242 0L5.636 17.364m12.728 0l-4.243-4.243m-4.242 0L5.636 6.636"></path>
                            </svg>
                            <span>Configuración</span>
                        </a>
                        <a href="/logout" class="user-menu-item" id="logoutMenuItem">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span>Cerrar Sesión</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <?php if (isset($footer_text) && !empty($footer_text)): ?>
                <p class="footer-text">
                    <?= htmlspecialchars($footer_text) ?>
                </p>
                <?php endif; ?>
                <?php if (isset($footer_contact) && $footer_contact): ?>
                <a href="<?= isset($footer_contact_url) ? htmlspecialchars($footer_contact_url) : '#' ?>" 
                   class="footer-link">
                    <?= isset($footer_contact_text) ? htmlspecialchars($footer_contact_text) : 'Click aquí' ?>
                </a>
                <?php endif; ?>
                <?php if (isset($footer_whatsapp) && $footer_whatsapp): ?>
                <a href="https://wa.me/<?= isset($footer_whatsapp_number) ? htmlspecialchars($footer_whatsapp_number) : '' ?>?text=<?= urlencode(isset($footer_whatsapp_text) ? $footer_whatsapp_text : 'Hola') ?>" 
                   class="footer-whatsapp" 
                   target="_blank" 
                   rel="noopener noreferrer">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    WhatsApp
                </a>
                <?php endif; ?>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= gac_name() ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="/assets/js/components/modal.js"></script>
    <script src="/assets/js/main.js"></script>
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
