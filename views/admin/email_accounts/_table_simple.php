<?php
/**
 * GAC - Tabla simplificada de correos (solo lectura, sin acciones)
 * Para vistas filtradas por dominio (Gmail, Outlook, Pocoyoni)
 */
?>

<div class="table-container">
    <table class="admin-table" id="emailAccountsTable">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>Correo</th>
                <th style="width: 18%;">Fecha registro</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if (empty($email_accounts)): ?>
                <tr>
                    <td colspan="3" class="text-center">
                        <p class="empty-message">No hay correos registrados.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($email_accounts as $account): ?>
                    <tr>
                        <td><?= (int)$account['id'] ?></td>
                        <td class="email-cell"><?= htmlspecialchars($account['email'] ?? '') ?></td>
                        <td><span class="sync-time"><?= !empty($account['created_at']) ? date('d/m/Y H:i', strtotime($account['created_at'])) : '—' ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($total_pages) && $total_pages > 1): ?>
<div class="pagination-container">
    <div class="pagination-info">
        Mostrando <?= min($per_page > 0 ? $per_page : $total_records, $total_records) ?> de <?= $total_records ?> registros
    </div>
    <div class="pagination-controls">
        <?php if ($current_page > 1): ?>
            <button class="btn btn-secondary btn-sm" data-page="<?= $current_page - 1 ?>">Anterior</button>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <button class="btn btn-sm <?= $i == $current_page ? 'btn-primary' : 'btn-secondary' ?>" data-page="<?= $i ?>"><?= $i ?></button>
        <?php endfor; ?>
        <?php if ($current_page < $total_pages): ?>
            <button class="btn btn-secondary btn-sm" data-page="<?= $current_page + 1 ?>">Siguiente</button>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
