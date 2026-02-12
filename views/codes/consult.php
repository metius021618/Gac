<?php
/**
 * GAC - Vista de Consulta de Códigos
 * 
 * Esta es la vista principal donde los usuarios consultan sus códigos
 */

// Incluir layout
$content = ob_start();
?>

<div class="consult-container">
    <div class="consult-wrapper">
        <!-- Logo Section -->
        <div class="logo-section">
            <img src="/assets/images/logocamb.png" alt="GAC" class="consult-logo">
        </div>

        <!-- Main Card -->
        <div class="consult-card">
            <div class="card-header">
                <h1 class="card-title">Consulta tu Código</h1>
            </div>

            <!-- Form -->
            <form id="consultForm" class="consult-form" novalidate>
                <!-- Email Input -->
                <div class="form-group">
                    <label for="email" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Correo Electrónico
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="tu@correo.com"
                        required
                        autocomplete="email"
                    >
                    <span class="form-error" id="emailError"></span>
                </div>

                <!-- Usuario Input -->
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
                        placeholder="Tu nombre de usuario"
                        required
                        autocomplete="username"
                    >
                    <span class="form-error" id="usernameError"></span>
                </div>

                <!-- Platform Select -->
                <div class="form-group">
                    <label for="platform" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        Plataforma
                    </label>
                    <select 
                        id="platform" 
                        name="platform" 
                        class="form-select" 
                        required
                    >
                        <option value="" disabled selected>Selecciona una plataforma</option>
                        <?php foreach ($platforms as $key => $name): ?>
                            <option value="<?= htmlspecialchars($key) ?>">
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="platformError"></span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                    <span class="btn-text">Consultar Código</span>
                    <span class="btn-loader" id="btnLoader">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 11-6.219-8.56"/>
                        </svg>
                    </span>
                </button>
            </form>
        </div>
        
        <!-- Modal de Email Completo -->
        <div id="emailModal" class="email-modal hidden">
            <div class="email-modal-overlay"></div>
            <div class="email-modal-container">
                <div class="email-modal-header">
                    <h2 class="email-modal-title">Email Completo</h2>
                    <button type="button" class="email-modal-close" id="closeEmailModal" aria-label="Cerrar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="email-modal-content" id="emailModalContent">
                    <div class="email-info">
                        <div class="email-info-row">
                            <span class="email-info-label">De:</span>
                            <span class="email-info-value" id="emailModalFrom">-</span>
                        </div>
                        <div class="email-info-row">
                            <span class="email-info-label">Asunto:</span>
                            <span class="email-info-value" id="emailModalSubject">-</span>
                        </div>
                        <div class="email-info-row">
                            <span class="email-info-label">Fecha:</span>
                            <span class="email-info-value" id="emailModalDate">-</span>
                        </div>
                    </div>
                    <div class="email-body-container">
                        <div class="email-body" id="emailModalBody"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Configurar variables para el layout (nombre GAC y propósito para verificación Google)
$title = $title ?? 'Consulta tu Código';
$description = 'GAC es la aplicación para consultar códigos de acceso recibidos por correo (Disney+, Netflix y otras plataformas). Consulta tu código de forma rápida y segura.';
$show_nav = false;
$footer_text = 'Tienes alguna duda, comunicate conmigo';
$footer_contact = false;
$footer_whatsapp = true;
$footer_whatsapp_number = '920859333';
$footer_whatsapp_text = 'Hola, tengo una duda';
$additional_css = ['/assets/css/consult.css'];
$additional_js = ['/assets/js/consult.js'];

// Incluir layout
require base_path('views/layouts/main.php');
?>
