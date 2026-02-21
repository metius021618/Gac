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

    <div class="admin-content">
        <div class="settings-container">

            <!-- Cuenta Gmail matriz (solo una) -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <h2 class="settings-section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Configurar cuenta Google (Gmail matriz)
                    </h2>
                    <p class="settings-section-description">
                        La cuenta Gmail desde la que se leen todos los correos reenviados. Solo puede haber una. El destinatario real de cada correo se obtiene de los headers (To, X-Original-To).
                    </p>
                </div>
                <div class="form-card gmail-matrix-card">
                    <?php if (!empty($gmail_matrix_account) && !empty($gmail_matrix_account['email'])): ?>
                        <p class="gmail-matrix-label">Cuenta configurada:</p>
                        <p class="gmail-matrix-email"><?= htmlspecialchars($gmail_matrix_account['email']) ?></p>
                    <?php else: ?>
                        <p class="gmail-matrix-empty">Aún no hay ninguna cuenta Gmail configurada.</p>
                    <?php endif; ?>
                    <a href="/gmail/connect?from=settings" class="btn btn-primary" style="background-color: #ea4335;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 6px;">
                            <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.636H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L12 9.313l8.073-5.82C21.69 2.28 24 3.434 24 5.457z"/>
                        </svg>
                        <?= !empty($gmail_matrix_account) ? 'Cambiar cuenta Gmail matriz' : 'Configurar cuenta Gmail matriz' ?>
                    </a>
                </div>
            </div>

            <!-- Lector continuo (sin cron) -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <h2 class="settings-section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Lector continuo de correos
                    </h2>
                    <p class="settings-section-description">
                        Ejecuta los lectores (Gmail, Outlook, IMAP) cada pocos segundos en segundo plano, sin depender del cron del servidor. El intervalo se configura en <code>.env</code> con <code>CRON_READER_LOOP_SECONDS</code> (por defecto 10 segundos).
                    </p>
                </div>
                <div class="form-card gmail-matrix-card">
                    <p class="gmail-matrix-label">Estado:</p>
                    <p id="readerLoopStatus" class="gmail-matrix-email" style="margin-bottom: 0.5rem;">—</p>
                    <button type="button" id="btnStartReaderLoop" class="btn btn-primary">
                        Iniciar lector continuo
                    </button>
                    <p id="readerLoopMessage" class="gmail-matrix-empty" style="margin-top: 0.5rem; margin-bottom: 0;"></p>
                </div>
            </div>

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

                    <!-- Acceso maestro a Consulta tu código -->
                    <div class="settings-section-header" style="margin-top: 2rem;">
                        <h2 class="settings-section-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            Acceso maestro a consulta de códigos
                        </h2>
                        <p class="settings-section-description">
                            Si está habilitado, un administrador logueado puede usar la clave maestra en "Consulta tu código" y ver el último código de la plataforma (de cualquier cuenta), no solo el suyo.
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="form-label checkbox-label">
                            <input type="checkbox" id="master_consult_enabled" name="master_consult_enabled" value="1" <?= (!empty($master_consult_enabled) && $master_consult_enabled === '1') ? 'checked' : '' ?>>
                            Habilitar acceso maestro
                        </label>
                        <small class="form-help">Solo funciona si el usuario está logueado como administrador en el panel.</small>
                    </div>
                    <div class="form-group">
                        <label for="master_consult_username" class="form-label">Usuario/clave maestra</label>
                        <input type="text" id="master_consult_username" name="master_consult_username" class="form-input" 
                               value="<?= htmlspecialchars($master_consult_username ?? '') ?>" 
                               placeholder="Ej: MAESTRO o clave secreta">
                        <small class="form-help">El admin escribe este valor en el campo "Usuario" en Consulta tu código (y cualquier correo/plataforma) para ver el último código de esa plataforma.</small>
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

            <!-- Personalización de roles -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <h2 class="settings-section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Personalización de roles
                    </h2>
                    <p class="settings-section-description">
                        Asigna qué vistas puede ver cada rol. Haz clic en el lápiz para editar las secciones visibles y ver la vista demo en tiempo real.
                    </p>
                </div>
                <div class="roles-panel">
                    <div class="roles-list">
                        <?php foreach ($roles ?? [] as $role): ?>
                            <div class="role-row" data-role-id="<?= (int)$role['id'] ?>" data-role-name="<?= htmlspecialchars($role['display_name'] ?? $role['name']) ?>">
                                <span class="role-name"><?= htmlspecialchars($role['display_name'] ?? $role['name']) ?></span>
                                <button type="button" class="btn-icon btn-edit-role" title="Editar vistas de este rol">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($roles)): ?>
                            <p class="roles-empty">No hay roles configurados.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal editor de vistas por rol -->
<div id="roleViewsModal" class="modal hidden role-views-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container role-views-modal-container">
        <div class="modal-header">
            <h2 class="modal-title" id="roleViewsModalTitle">Vistas del rol</h2>
            <button type="button" class="modal-close" id="closeRoleViewsModal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-content role-views-modal-body">
        <div class="role-views-editor">
            <div class="role-preview-frame-wrap">
                <p class="role-preview-label">Vista que verá el usuario (tal cual en la página)</p>
                <iframe id="rolePreviewFrame" class="role-preview-frame" src="/admin/role-preview?views=" title="Vista previa"></iframe>
            </div>
            <div class="role-views-checkboxes-panel">
                <p class="role-views-panel-title">Secciones visibles</p>
                <p class="role-views-panel-desc">Marca las vistas y acciones que podrá usar este rol. Al marcar una vista, se despliega el submenú de acciones.</p>
                <div class="role-views-checkboxes" id="roleViewsCheckboxes">
                    <?php foreach ($role_views_config ?? [] as $v): $actions = $v['actions'] ?? []; ?>
                        <div class="role-view-item" data-view-key="<?= htmlspecialchars($v['key']) ?>">
                            <label class="role-view-parent">
                                <input type="checkbox" class="role-view-checkbox" name="view_keys[]" value="<?= htmlspecialchars($v['key']) ?>">
                                <span class="role-view-label"><?= htmlspecialchars($v['label']) ?></span>
                                <?php if (!empty($actions)): ?>
                                    <span class="role-view-expand-icon">▼</span>
                                <?php endif; ?>
                            </label>
                            <?php if (!empty($actions)): ?>
                            <div class="role-view-actions-submenu">
                                <?php foreach ($actions as $actionKey => $actionLabel): ?>
                                    <label class="role-view-action-label">
                                        <input type="checkbox" class="role-view-action-checkbox" data-view-key="<?= htmlspecialchars($v['key']) ?>" data-action="<?= htmlspecialchars($actionKey) ?>">
                                        <span><?= htmlspecialchars($actionLabel) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="role-views-actions">
                    <button type="button" class="btn btn-secondary" id="cancelRoleViewsBtn">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="saveRoleViewsBtn">Guardar vistas</button>
                </div>
            </div>
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
