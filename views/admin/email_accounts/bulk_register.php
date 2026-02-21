<?php
/**
 * GAC - Vista de Registro Masivo de Correos
 * Permite asignar múltiples correos con una sola clave de acceso y plataforma
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header" style="display: flex; justify-content: center; align-items: center; padding: 1rem 0;">
        <div class="bulk-tabs" style="display: flex; gap: 0.5rem; background: #f0f0f0; padding: 0.375rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <button type="button" class="bulk-tab active" data-tab="assign" style="padding: 0.625rem 1.25rem; border: none; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border-radius: 8px; cursor: pointer; font-weight: 600; color: white; box-shadow: 0 2px 4px rgba(0,123,255,0.3); transition: all 0.2s ease; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Asignar Correos Masivamente
            </button>
            <button type="button" class="bulk-tab" data-tab="delete" style="padding: 0.625rem 1.25rem; border: none; background: transparent; border-radius: 8px; cursor: pointer; font-weight: 500; color: #666; transition: all 0.2s ease; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle;">
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
                        placeholder="correo1@pocoyoni.com&#10;correo2@gmail.com&#10;correo3@hotmail.com"
                        rows="8"
                        required
                    ></textarea>
                    <span class="form-error" id="emailsError"></span>
                    <small class="form-help">Ingresa un correo por línea. Dominios permitidos: Pocoyoni, Gmail, Outlook, Hotmail, Live.</small>
                    <div class="form-group" style="margin-top: 0.5rem;">
                        <?php if (function_exists('user_can_action') && user_can_action('registro_masivo', 'agregar')): ?>
                        <button type="button" id="btnAddStock" class="btn btn-secondary">
                            Agregar como stock
                        </button>
                        <?php endif; ?>
                        <small class="form-help" style="display: block; margin-top: 0.25rem;">Registra los correos en el listado (sin asignar plataforma). Se mostrarán en Gmail, Hotmail o Pocoyoni según el dominio.</small>
                    </div>
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
                    <?php if (function_exists('user_can_action') && user_can_action('registro_masivo', 'agregar')): ?>
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
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Formulario Eliminar -->
        <div class="form-container" id="deleteFormContainer" style="display: none;">
            <form id="bulkDeleteForm" class="admin-form bulk-register-form" novalidate>
                <!-- Filtro de Plataforma -->
                <div class="form-group">
                    <label for="delete_platform_id" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        Plataforma <span class="required">*</span>
                    </label>
                    <select 
                        id="delete_platform_id" 
                        name="platform_id" 
                        class="form-select" 
                        required
                    >
                        <option value="" disabled selected>Seleccione plataforma a eliminar...</option>
                        <?php foreach ($platforms as $platform): ?>
                            <option value="<?= $platform['id'] ?>">
                                <?= htmlspecialchars($platform['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="deletePlatformError"></span>
                    <small class="form-help">Solo se eliminarán las asignaciones de esta plataforma. Si el correo tiene otras plataformas, esas se mantienen.</small>
                </div>

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
                    <small class="form-help">Ingresa un correo por línea. Solo se eliminarán las asignaciones de la plataforma seleccionada.</small>
                </div>

                <!-- Botones de acción -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger btn-bulk-delete">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span class="btn-text">Eliminar Masivamente</span>
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

    function setActiveTab(tab) {
        // Estilos para tab activo
        const activeStyles = {
            background: 'linear-gradient(135deg, #007bff 0%, #0056b3 100%)',
            color: 'white',
            boxShadow: '0 2px 4px rgba(0,123,255,0.3)',
            fontWeight: '600'
        };

        // Estilos para tab inactivo
        const inactiveStyles = {
            background: 'transparent',
            color: '#666',
            boxShadow: 'none',
            fontWeight: '500'
        };

        if (tab === 'assign') {
            // Activar asignar
            Object.assign(assignTab.style, activeStyles);
            assignTab.classList.add('active');
            assignContainer.style.display = 'block';
            
            // Desactivar eliminar
            Object.assign(deleteTab.style, inactiveStyles);
            deleteTab.classList.remove('active');
            deleteContainer.style.display = 'none';
        } else {
            // Activar eliminar
            Object.assign(deleteTab.style, activeStyles);
            deleteTab.classList.add('active');
            deleteContainer.style.display = 'block';
            
            // Desactivar asignar
            Object.assign(assignTab.style, inactiveStyles);
            assignTab.classList.remove('active');
            assignContainer.style.display = 'none';
        }
    }

    assignTab.addEventListener('click', function() {
        setActiveTab('assign');
    });

    deleteTab.addEventListener('click', function() {
        setActiveTab('delete');
    });

    // Asegurar estado inicial
    setActiveTab('assign');
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
