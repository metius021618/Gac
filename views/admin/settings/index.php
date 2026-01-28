<?php
/**
 * GAC - Vista de Configuración del Sistema
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3m15.364 6.364l-4.243-4.243m-4.242 0L5.636 17.364m12.728 0l-4.243-4.243m-4.242 0L5.636 6.636"></path>
            </svg>
            Configuración del Sistema
        </h1>
    </div>

    <div class="admin-content">
        <div class="settings-container">
            <!-- Sección de Sesión -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <h2 class="settings-section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Configuración de Sesión
                    </h2>
                    <p class="settings-section-description">
                        Configura el tiempo que se mantiene activa la sesión del usuario antes de requerir un nuevo inicio de sesión.
                    </p>
                </div>

                <form id="settingsForm" class="settings-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    
                    <div class="form-group">
                        <label for="session_timeout_hours" class="form-label">
                            <svg class="form-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Tiempo en sesión activo
                            <span class="required">*</span>
                        </label>
                        <select 
                            id="session_timeout_hours" 
                            name="session_timeout_hours" 
                            class="form-select" 
                            required
                        >
                            <?php 
                            $allowedHours = [1, 2, 3, 5, 7];
                            $currentHours = $session_timeout_hours ?? 1;
                            foreach ($allowedHours as $hours): 
                                $selected = ($currentHours == $hours) ? 'selected' : '';
                                $label = $hours == 1 ? '1 hora' : "{$hours} horas";
                            ?>
                                <option value="<?= $hours ?>" <?= $selected ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">
                            Selecciona el tiempo que permanecerá activa la sesión del usuario. Después de este tiempo, se requerirá un nuevo inicio de sesión.
                        </small>
                        <span class="form-error" id="sessionTimeoutHoursError"></span>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelBtn">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-text">Guardar Configuración</span>
                            <span class="btn-loader" style="display: none;">
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
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Configuración del Sistema';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/settings.css'];
$additional_js = ['/assets/js/admin/settings.js'];

require base_path('views/layouts/main.php');
?>
