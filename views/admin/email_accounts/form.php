<?php
/**
 * GAC - Vista de Formulario de Cuenta de Email
 */

use Gac\Repositories\UserAccessRepository;

$isEdit = ($mode === 'edit' && $email_account !== null);
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
        <h1 class="admin-title"><?= $isEdit ? 'Editar' : 'Agregar' ?> Cuenta de Email</h1>
    </div>

    <div class="admin-content">
        <div class="form-container">
            <form id="emailAccountForm" class="admin-form" novalidate>
                <?php if ($isEdit): ?>
                    <input type="hidden" id="id" name="id" value="<?= $email_account['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <svg class="form-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Correo Electrónico <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="correo@dominio.com"
                        value="<?= $isEdit ? htmlspecialchars($email_account['email']) : '' ?>"
                        required
                    >
                    <span class="form-error" id="emailError"></span>
                    <small class="form-help">Email del dominio que recibirá los códigos</small>
                </div>

                <div class="form-group">
                    <label for="imap_user" class="form-label">
                        <svg class="form-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Acceso (Usuario IMAP) <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="imap_user" 
                        name="imap_user" 
                        class="form-input" 
                        placeholder="usuario@dominio.com"
                        value="<?= $isEdit ? htmlspecialchars($email_account['imap_user']) : '' ?>"
                        required
                    >
                    <span class="form-error" id="imapUserError"></span>
                    <small class="form-help">Usuario o contraseña para acceder al buzón IMAP</small>
                </div>

                <?php if ($isEdit): ?>
                    <?php
                    // Obtener plataformas asignadas a este correo
                    $platforms = [];
                    try {
                        if (!empty($email_account['email'])) {
                            $userAccessRepo = new UserAccessRepository();
                            $platforms = $userAccessRepo->getPlatformsByEmail($email_account['email']);
                        }
                    } catch (\Exception $e) {
                        error_log("Error al obtener plataformas en form.php: " . $e->getMessage());
                        $platforms = [];
                    }
                    ?>
                    <div class="form-group">
                        <label class="form-label">
                            <svg class="form-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                            Plataformas Asignadas
                        </label>
                        <div class="platforms-display">
                            <?php if (!empty($platforms)): ?>
                                <?php foreach ($platforms as $platform): ?>
                                    <span class="platform-badge"><?= htmlspecialchars($platform) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="platform-badge empty">Sin plataformas asignadas</span>
                            <?php endif; ?>
                        </div>
                        <small class="form-help">Las plataformas se asignan desde "Registro de Accesos"</small>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text"><?= $isEdit ? 'Actualizar' : 'Guardar' ?> Cuenta</span>
                        <span class="btn-loader">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 12a9 9 0 11-6.219-8.56"/>
                            </svg>
                        </span>
                    </button>
                    <a href="/admin/email-accounts" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? ($isEdit ? 'Editar Cuenta de Email' : 'Agregar Cuenta de Email');
$show_nav = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css', '/assets/css/admin/building.css'];
$additional_js = ['/assets/js/admin/email_accounts.js'];

require base_path('views/layouts/main.php');
?>
