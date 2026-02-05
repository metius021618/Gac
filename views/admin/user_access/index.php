<?php
/**
 * GAC - Vista de Registro de Accesos
 * Formulario para asignar/actualizar contraseñas de acceso
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            Asignar/Actualizar Contraseña
        </h1>
    </div>

    <?php if (!empty($_SESSION['gmail_success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($_SESSION['gmail_success']) ?>
            <?php unset($_SESSION['gmail_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['gmail_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['gmail_error']) ?>
            <?php unset($_SESSION['gmail_error']); ?>
        </div>
    <?php endif; ?>
    <?php
    $showOutlookSuccess = !empty($_SESSION['outlook_success']) || (!empty($_GET['outlook_connected']) && $_GET['outlook_connected'] === '1');
    $outlookSuccessText = !empty($_SESSION['outlook_success']) ? $_SESSION['outlook_success'] : 'Cuenta Outlook conectada correctamente.';
    if ($showOutlookSuccess): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($outlookSuccessText) ?>
            <?php unset($_SESSION['outlook_success']); ?>
        </div>
        <?php if (!empty($_GET['outlook_connected'])): ?>
        <script>
        (function(){ var u = new URL(window.location.href); u.searchParams.delete('outlook_connected'); if (u.search !== window.location.search) window.history.replaceState({}, '', u.pathname + u.search); })();
        </script>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['outlook_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['outlook_error']) ?>
            <?php unset($_SESSION['outlook_error']); ?>
        </div>
    <?php endif; ?>

    <div class="admin-content">
        <!-- Conectar Gmail y Outlook -->
        <div class="form-card" style="margin-bottom: 1.5rem;">
            <p class="form-label" style="margin-bottom: 0.75rem;">Conectar cuenta Gmail o Outlook para que el sistema pueda leer correos de esa cuenta</p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                <a href="/gmail/connect" class="btn btn-primary" id="gmailConnectBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.636H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L12 9.313l8.073-5.82C21.69 2.28 24 3.434 24 5.457z"/>
                    </svg>
                    Conectar Gmail
                </a>
                <a href="/outlook/connect" class="btn btn-primary" id="outlookConnectBtn" style="background-color: #0078d4;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        <circle cx="12" cy="11" r="3" fill="currentColor"/>
                    </svg>
                    Conectar Outlook
                </a>
            </div>
        </div>

        <!-- Formulario -->
        <div class="form-card">
            <form id="userAccessForm" class="user-access-form">
                <!-- Campo Correo -->
                <div class="form-group">
                    <label for="email" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Correo
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="correo@ejemplo.com"
                        value="<?= htmlspecialchars($prefill_email ?? '') ?>"
                        required
                        autocomplete="email"
                    >
                    <span class="form-error" id="emailError"></span>
                </div>

                <!-- Campo Contraseña -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Contraseña
                    </label>
                    <input 
                        type="text" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Clave para el buzón"
                        required
                        autocomplete="off"
                    >
                    <span class="form-error" id="passwordError"></span>
                </div>

                <!-- Campo Plataforma -->
                <div class="form-group">
                    <label for="platform_id" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        Plataforma
                    </label>
                    <select 
                        id="platform_id" 
                        name="platform_id" 
                        class="form-select" 
                        required
                    >
                        <?php $prefill_platform_id = (int)($prefill_platform_id ?? 0); ?>
                        <option value="" disabled <?= $prefill_platform_id ? '' : 'selected' ?>>Seleccione una plataforma</option>
                        <?php foreach ($platforms as $platform): ?>
                            <option value="<?= $platform['id'] ?>" <?= ($platform['id'] == $prefill_platform_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($platform['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="platformError"></span>
                </div>

                <!-- Botón Guardar -->
                <button type="submit" class="btn btn-primary btn-save">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Guardar
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Registro de Accesos';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/user_access.css'];
$additional_js = ['/assets/js/admin/user_access.js'];

require base_path('views/layouts/main.php');
?>
