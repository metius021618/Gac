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
            <img src="/assets/images/logocamb.png" alt="<?= gac_name() ?>" class="consult-logo">
        </div>

        <!-- Main Card -->
        <div class="consult-card">
            <div class="card-header">
                <h1 class="card-title">Consulta tu Código</h1>
                <p class="card-subtitle">Ingresa tu correo electrónico, usuario y selecciona la plataforma</p>
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

            <!-- Result Section -->
            <div id="resultSection" class="result-section hidden">
                <div class="result-card" id="resultCard">
                    <div class="result-icon" id="resultIcon"></div>
                    <h3 class="result-title" id="resultTitle"></h3>
                    <p class="result-message" id="resultMessage"></p>
                    <div class="result-code" id="resultCode"></div>
                    <button type="button" class="btn btn-secondary btn-copy hidden" id="copyBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        Copiar Código
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <p class="info-text">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                Los códigos son de un solo uso y se marcan como consumidos al consultarlos
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Configurar variables para el layout
$title = $title ?? 'Consulta tu Código';
$description = 'Consulta tus códigos de acceso para plataformas de streaming y servicios digitales';
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
