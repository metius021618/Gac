<?php
/**
 * GAC - Lista de cuentas del Revendedor
 * Columnas: Correo - Plataforma - Asignado
 */

$content = ob_start();
?>

<div class="revendedor-container">
    <div class="revendedor-header">
        <h1 class="revendedor-title">Lista de cuentas</h1>
        <p class="revendedor-subtitle">
            Solo se muestran las cuentas que se te han asignado (vendidas) como revendedor.
        </p>
    </div>

    <div class="revendedor-table-wrapper">
        <table class="revendedor-table" id="revendedorAccountsTable">
            <thead>
                <tr>
                    <th>Correo</th>
                    <th>Plataforma</th>
                    <th>Asignado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="3" class="revendedor-empty">No tienes cuentas asignadas todavía.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): 
                        $access = $row['access'];
                        $subusers = $row['subusers'] ?? [];
                        $asignado = !empty($subusers);
                        $accessId = (int) $access['id'];
                    ?>
                    <tr class="revendedor-main-row" data-access-id="<?= $accessId ?>">
                        <td><?= htmlspecialchars($access['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($access['platform_name'] ?? '') ?></td>
                        <td class="revendedor-asignado-cell">
                            <?php if ($asignado): ?>
                                <button type="button" class="revendedor-toggle-subusers" data-access-id="<?= $accessId ?>" aria-expanded="false">
                                    <span class="revendedor-asignado-label">Si</span>
                                    <span class="revendedor-toggle-icon">▼</span>
                                </button>
                            <?php else: ?>
                                <span class="revendedor-asignado-label revendedor-asignado-no">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($asignado): ?>
                    <tr class="revendedor-subusers-row" data-parent-id="<?= $accessId ?>" style="display:none;">
                        <td colspan="3">
                            <div class="revendedor-subusers-box">
                                <div class="revendedor-subusers-title">Usuarios asignados:</div>
                                <ul class="revendedor-subusers-list">
                                    <?php foreach ($subusers as $sub): ?>
                                        <li class="revendedor-subuser-item">
                                            <span class="revendedor-subuser-name"><?= htmlspecialchars($sub['username']) ?></span>
                                            <form method="post" action="/revendedor/subusuario/eliminar" class="revendedor-subuser-delete-form">
                                                <input type="hidden" name="id" value="<?= (int) $sub['id'] ?>">
                                                <button type="submit" class="revendedor-subuser-delete-btn" title="Eliminar usuario asignado">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Lista de cuentas';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css'];
$additional_js = ['/assets/js/revendedor.js'];

require base_path('views/layouts/main.php');
