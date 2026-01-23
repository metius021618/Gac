<?php
/**
 * GAC - Vista de Login
 */

$csrfToken = \Gac\Controllers\AuthController::generateCsrfToken();
$content = ob_start();
?>

<div class="login-container">
    <div class="login-wrapper">
        <!-- Logo Section -->
        <div class="login-logo-section">
            <img src="/assets/images/logocamb.png" alt="<?= gac_name() ?>" class="login-logo">
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">Iniciar Sesión</h1>
                <p class="login-subtitle">Ingresa tus credenciales para acceder</p>
            </div>

            <!-- Form -->
            <form id="loginForm" class="login-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Username Input -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Usuario
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Ingresa tu usuario"
                        required
                        autocomplete="username"
                        autofocus
                    >
                    <span class="form-error" id="usernameError"></span>
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Ingresa tu contraseña"
                        required
                        autocomplete="current-password"
                    >
                    <span class="form-error" id="passwordError"></span>
                </div>

                <!-- Remember Me -->
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <span>Recordar sesión</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                    <span class="btn-text">Iniciar Sesión</span>
                    <span class="btn-loader" id="btnLoader">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 11-6.219-8.56"/>
                        </svg>
                    </span>
                </button>
            </form>

            <!-- Security Info -->
            <div class="login-security-info">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Conexión segura protegida</span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Iniciar Sesión';
$show_nav = false;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/auth/login.css'];
$additional_js = ['/assets/js/auth/login.js'];

require base_path('views/layouts/main.php');
?>
