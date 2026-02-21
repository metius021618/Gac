<?php
/**
 * GAC - Tabla simplificada de correos (solo lectura, sin acciones)
 * Para vistas filtradas por dominio (Gmail, Outlook, Pocoyoni)
 */
$filter = $filter ?? '';
$email_view_key = !empty($filter) ? 'listar_' . $filter : 'listar_correos';
$can_delete_filtered = function_exists('user_can_action') && user_can_action($email_view_key, 'eliminar');
?>

<div class="table-container">
    <table class="admin-table" id="emailAccountsTable">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th class="text-center">Correo</th>
                <th style="width: 14%;">Asignado</th>
                <th style="width: 18%;">Actividad</th>
                <th style="width: 80px;">Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if (empty($email_accounts)): ?>
                <tr>
                    <td colspan="5" class="text-center">
                        <p class="empty-message">No hay correos registrados.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($email_accounts as $account): ?>
                    <?php 
                    $asignado = !empty($account['asignado']);
                    $filterType = $filter ?? '';
                    ?>
                    <tr data-id="<?= (int)$account['id'] ?>" data-filter="<?= htmlspecialchars($filterType) ?>">
                        <td><?= (int)$account['id'] ?></td>
                        <td class="email-cell text-center"><?= htmlspecialchars($account['email'] ?? '') ?></td>
                        <td class="text-center">
                            <?php if ($asignado): ?>
                                <span class="badge status-active">Sí</span>
                            <?php else: ?>
                                <span class="badge status-inactive">No</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="sync-time"><?= !empty($account['updated_at']) ? date('d/m/Y H:i', strtotime($account['updated_at'])) : (!empty($account['created_at']) ? date('d/m/Y H:i', strtotime($account['created_at'])) : '—') ?></span></td>
                        <td class="actions-cell text-center">
                            <?php if ($can_delete_filtered): ?>
                            <button type="button" 
                                    class="btn-icon btn-delete-filtered" 
                                    data-id="<?= (int)$account['id'] ?>"
                                    data-email="<?= htmlspecialchars($account['email'] ?? '') ?>"
                                    data-filter="<?= htmlspecialchars($filterType) ?>"
                                    title="Eliminar este correo">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$total_records = (int)($total_records ?? 0);
$total_pages = (int)($total_pages ?? 1);
$current_page = (int)($current_page ?? 1);
$per_page = (int)($per_page ?? 15);
$from = $total_records === 0 ? 0 : (($current_page - 1) * $per_page) + 1;
$to = $per_page > 0 ? min($current_page * $per_page, $total_records) : $total_records;
?>
<div class="pagination-container">
    <div class="pagination-info">
        Mostrando <strong><?= $from ?></strong> - <strong><?= $to ?></strong> de <strong><?= number_format($total_records) ?></strong> registros
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="pagination-controls">
        <button class="pagination-btn" data-page="<?= $current_page - 1 ?>" <?= $current_page <= 1 ? 'disabled' : '' ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
            Anterior
        </button>
        <div class="pagination-pages">
            <?php
            $startPage = max(1, $current_page - 2);
            $endPage = min($total_pages, $current_page + 2);
            if ($startPage > 1): ?>
                <button class="pagination-page" data-page="1">1</button>
                <?php if ($startPage > 2): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <button class="pagination-page <?= $i === $current_page ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></button>
            <?php endfor; ?>
            <?php if ($endPage < $total_pages): ?>
                <?php if ($endPage < $total_pages - 1): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
                <button class="pagination-page" data-page="<?= $total_pages ?>"><?= $total_pages ?></button>
            <?php endif; ?>
        </div>
        <button class="pagination-btn" data-page="<?= $current_page + 1 ?>" <?= $current_page >= $total_pages ? 'disabled' : '' ?>>
            Siguiente
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
    </div>
    <?php endif; ?>
</div>
