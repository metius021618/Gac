<?php
/**
 * GAC - Vista de Registro Masivo de Correos
 * Permite asignar múltiples correos con una sola clave de acceso y plataforma
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <div class="building-header">
            <a href="/admin/email-accounts" class="building-back-button">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver
            </a>
        </div>
        <h1 class="admin-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Asignar Correos Masivamente
        </h1>
    </div>

    <div class="admin-content">
        <div class="form-container">
            <form id="bulkRegisterForm" class="admin-form bulk-register-form" novalidate>
                <!-- Campo Correos Electrónicos -->
                <div class="form-group">
                    <label for="emails" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Correos Electrónicos <span class="required">*</span>
                    </label>
                    <textarea 
                        id="emails" 
                        name="emails" 
                        class="form-textarea" 
                        placeholder="correo1@pocoyoni.com&#10;correo2@pocoyoni.com&#10;correo3@pocoyoni.com"
                        rows="8"
                        required
                    ></textarea>
                    <span class="form-error" id="emailsError"></span>
                    <small class="form-help">Ingresa un correo por línea. Solo se aceptan correos del dominio pocoyoni.com</small>
                </div>

                <!-- Campo Código de Acceso -->
                <div class="form-group">
                    <label for="access_code" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Código de Acceso <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="access_code" 
                        name="access_code" 
                        class="form-input" 
                        placeholder="Usuario IMAP o contraseña"
                        required
                        autocomplete="off"
                    >
                    <span class="form-error" id="accessCodeError"></span>
                    <small class="form-help">Este código se asignará a todos los correos ingresados</small>
                </div>

                <!-- Campo Plataforma -->
                <div class="form-group">
                    <label for="platform_id" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        Plataforma <span class="required">*</span>
                    </label>
                    <select 
                        id="platform_id" 
                        name="platform_id" 
                        class="form-select" 
                        required
                    >
                        <option value="" disabled selected>Seleccione...</option>
                        <?php foreach ($platforms as $platform): ?>
                            <option value="<?= $platform['id'] ?>">
                                <?= htmlspecialchars($platform['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="platformError"></span>
                </div>

                <!-- Botones de acción -->
                <div class="form-actions">
                    <a href="/admin/email-accounts" class="btn btn-secondary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Volver
                    </a>
                    <button type="submit" class="btn btn-primary btn-bulk-assign">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="12" y1="8" x2="12" y2="16"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                        <span class="btn-text">Asignar Masivamente</span>
                        <span class="btn-loader">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 12a9 9 0 11-6.219-8.56"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Asignar Correos Masivamente';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css', '/assets/css/admin/bulk_register.css'];
$additional_js = ['/assets/js/admin/bulk_register.js'];

require base_path('views/layouts/main.php');
?>
