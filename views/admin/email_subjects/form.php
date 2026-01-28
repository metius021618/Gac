<?php
/**
 * GAC - Vista de Formulario de Asunto de Email
 */

$isEdit = ($mode === 'edit' && $subject !== null);
$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <div class="building-header">
            <a href="/admin/email-subjects" class="building-back-button">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver
            </a>
        </div>
        <h1 class="admin-title"><?= $isEdit ? 'Editar' : 'Nuevo' ?> Asunto</h1>
    </div>

    <div class="admin-content">
        <div class="form-container">
            <form id="emailSubjectForm" class="admin-form email-subjects-form" novalidate>
                <?php if ($isEdit): ?>
                    <input type="hidden" id="id" name="id" value="<?= $subject['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="platform_id" class="form-label">
                        <svg class="form-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                        <option value="" disabled <?= !$isEdit ? 'selected' : '' ?>>Seleccione una plataforma</option>
                        <?php foreach ($platforms as $platform): ?>
                            <option value="<?= $platform['id'] ?>" 
                                    <?= ($isEdit && $subject['platform_id'] == $platform['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($platform['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="platformError"></span>
                </div>

                <div class="form-group">
                    <label for="subject_line" class="form-label">
                        <svg class="form-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Asunto <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="subject_line" 
                        name="subject_line" 
                        class="form-input" 
                        placeholder="Ej: Tu código de verificación de Netflix"
                        value="<?= $isEdit ? htmlspecialchars($subject['subject_line']) : '' ?>"
                        required
                        maxlength="500"
                    >
                    <span class="form-error" id="subjectLineError"></span>
                    <small class="form-help">Asunto del correo electrónico que se buscará para identificar la plataforma</small>
                </div>

                <div class="form-actions">
                    <a href="/admin/email-subjects" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text"><?= $isEdit ? 'Actualizar' : 'Guardar' ?> Asunto</span>
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

$title = $title ?? ($isEdit ? 'Editar Asunto' : 'Nuevo Asunto');
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_subjects.css', '/assets/css/admin/building.css'];
$additional_js = ['/assets/js/admin/email_subjects.js'];

require base_path('views/layouts/main.php');
?>
