<?php
/**
 * GAC - Lista de cuentas del Revendedor
 * Columnas: Correo - Plataforma - Asignado
 */

$content = ob_start();
?>

<div class="admin-container revendedor-lista-container">
    <div class="admin-header revendedor-lista-header">
        <a href="/revendedor/dashboard" class="revendedor-btn-back" title="Volver al panel">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Atrás
        </a>
        <h1 class="admin-title">Lista de cuentas</h1>
    </div>

    <div class="admin-content">
        <div class="table-controls">
            <div class="search-input-wrapper">
                <span class="search-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text"
                       id="revendedorSearch"
                       class="search-input"
                       placeholder="Buscar por correo...">
            </div>
        </div>

        <div class="table-container">
        <table class="admin-table" id="revendedorAccountsTable">
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
                    <tr class="revendedor-subusers-row is-hidden" data-parent-id="<?= $accessId ?>">
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
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Lista de cuentas';
$show_nav = true;
$hide_main_nav_links = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css', '/assets/css/admin/revendedor.css'];
$additional_js = ['/assets/js/revendedor.js'];

require base_path('views/layouts/main.php');
