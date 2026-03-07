<?php
/**
 * GAC - Vista "Actualizar usuario"
 * Editar un registro existente por ID (correo, usuario, plataforma).
 */

$content = ob_start();
$access = $access ?? [];
$access_id = (int)($access['id'] ?? 0);
$prefill_email = $access['email'] ?? '';
$prefill_password = $access['password'] ?? '';
$prefill_platform_id = (int)($access['platform_id'] ?? 0);
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Actualizar usuario
        </h1>
    </div>

    <div class="admin-content">
        <div class="form-card">
            <form id="userAccessForm" class="user-access-form" action="/admin/user-access/actualizar" method="post">
                <input type="hidden" name="id" value="<?= $access_id ?>">
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
                        value="<?= htmlspecialchars($prefill_email) ?>"
                        required
                        autocomplete="email"
                    >
                    <span class="form-error" id="emailError"></span>
                </div>

                <!-- Campo Usuario -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Usuario
                    </label>
                    <input 
                        type="text" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Usuario de acceso"
                        value="<?= htmlspecialchars($prefill_password) ?>"
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
                    <select id="platform_id" name="platform_id" class="form-select" required>
                        <option value="">Seleccione una plataforma</option>
                        <?php foreach ($platforms as $platform): ?>
                            <option value="<?= $platform['id'] ?>" <?= ($platform['id'] == $prefill_platform_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($platform['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="platformError"></span>
                </div>

                <?php if (function_exists('user_can_action') && user_can_action('registro_acceso', 'editar')): ?>
                <button type="submit" class="btn btn-primary btn-save">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Guardar
                </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Actualizar usuario';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/user_access.css'];
$additional_js = ['/assets/js/admin/user_access.js'];

require base_path('views/layouts/main.php');
?>
