<?php
/**
 * GAC - Vista de Registro de Asuntos
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Registro de Asuntos</h1>
        <p class="admin-subtitle">Asuntos de emails configurados por plataforma</p>
    </div>

    <div class="admin-content">
        <?php if (empty($subjects_by_platform)): ?>
            <div class="empty-message">
                <p>No hay asuntos configurados</p>
            </div>
        <?php else: ?>
            <?php foreach ($subjects_by_platform as $platform => $subjects): ?>
                <div class="platform-subjects-card" style="margin-bottom: var(--spacing-xl);">
                    <div class="card-header" style="background: rgba(255, 255, 255, 0.05); padding: var(--spacing-md); border-radius: var(--border-radius) var(--border-radius) 0 0; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0; color: var(--text-primary); text-transform: capitalize;">
                            <?= htmlspecialchars(ucfirst($platform)) ?>
                        </h3>
                    </div>
                    <div class="card-body" style="background: rgba(255, 255, 255, 0.02); padding: var(--spacing-lg); border-radius: 0 0 var(--border-radius) var(--border-radius);">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($subjects as $index => $subject): ?>
                                <li style="padding: var(--spacing-sm) 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: var(--text-secondary);">
                                    <span style="color: var(--color-primary); font-weight: 600; margin-right: var(--spacing-sm);"><?= $index + 1 ?>.</span>
                                    <?= htmlspecialchars($subject) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Registro de Asuntos';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css'];

require base_path('views/layouts/main.php');
?>
