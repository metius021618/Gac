<?php
/**
 * GAC - Vista de Registro Masivo de Correos
 * Permite asignar múltiples correos con una sola clave de acceso y plataforma
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header" style="display: flex; justify-content: flex-end; align-items: center;">
        <div class="bulk-tabs" style="display: flex; gap: 0.5rem; background: #f5f5f5; padding: 0.25rem; border-radius: 8px;">
            <button type="button" class="bulk-tab active" data-tab="assign" style="padding: 0.5rem 1rem; border: none; background: white; border-radius: 6px; cursor: pointer; font-weight: 500; color: #333; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Asignar Correos Masivamente
            </button>
            <button type="button" class="bulk-tab" data-tab="delete" style="padding: 0.5rem 1rem; border: none; background: transparent; border-radius: 6px; cursor: pointer; font-weight: 500; color: #666;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Eliminar Correos Masivamente
            </button>
        </div>
    </div>

    <div class="admin-content">
        <!-- Formulario Asignar -->
        <div class="form-container" id="assignFormContainer">
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

        <!-- Formulario Eliminar -->
        <div class="form-container" id="deleteFormContainer" style="display: none;">
            <form id="bulkDeleteForm" class="admin-form bulk-register-form" novalidate>
                <!-- Campo Correos Electrónicos -->
                <div class="form-group">
                    <label for="emails_delete" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Correos Electrónicos a Eliminar <span class="required">*</span>
                    </label>
                    <textarea 
                        id="emails_delete" 
                        name="emails" 
                        class="form-textarea" 
                        placeholder="correo1@pocoyoni.com&#10;correo2@pocoyoni.com&#10;correo3@pocoyoni.com"
                        rows="8"
                        required
                    ></textarea>
                    <span class="form-error" id="emailsDeleteError"></span>
                    <small class="form-help">Ingresa un correo por línea. Se eliminarán de la base de datos.</small>
                </div>

                <!-- Botones de acción -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger btn-bulk-delete">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span class="btn-text">Eliminar Masivamente</span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const assignTab = document.querySelector('.bulk-tab[data-tab="assign"]');
    const deleteTab = document.querySelector('.bulk-tab[data-tab="delete"]');
    const assignContainer = document.getElementById('assignFormContainer');
    const deleteContainer = document.getElementById('deleteFormContainer');

    assignTab.addEventListener('click', function() {
        assignTab.classList.add('active');
        assignTab.style.background = 'white';
        assignTab.style.color = '#333';
        assignTab.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        deleteTab.classList.remove('active');
        deleteTab.style.background = 'transparent';
        deleteTab.style.color = '#666';
        deleteTab.style.boxShadow = 'none';
        assignContainer.style.display = 'block';
        deleteContainer.style.display = 'none';
    });

    deleteTab.addEventListener('click', function() {
        deleteTab.classList.add('active');
        deleteTab.style.background = 'white';
        deleteTab.style.color = '#333';
        deleteTab.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        assignTab.classList.remove('active');
        assignTab.style.background = 'transparent';
        assignTab.style.color = '#666';
        assignTab.style.boxShadow = 'none';
        assignContainer.style.display = 'none';
        deleteContainer.style.display = 'block';
    });
});
</script>

<?php
$content = ob_get_clean();

$title = $title ?? 'Asignar Correos Masivamente';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css', '/assets/css/admin/bulk_register.css', '/assets/css/admin/building.css'];
$additional_js = ['/assets/js/admin/bulk_register.js'];

require base_path('views/layouts/main.php');
?>
