<?php
/**
 * GAC - Vista Parcial de Tabla de Plataformas
 * Para actualización AJAX
 */
?>

<div class="table-container">
    <table class="admin-table" id="platformsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Slug</th>
                <th>Estado</th>
                <th>Act/Desc</th>
                <th>Fecha Creación</th>
                <th>Última Actualización</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($platforms)): ?>
                <tr>
                    <td colspan="7" class="text-center">
                        <p class="empty-message">No hay plataformas registradas</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($platforms as $platform): ?>
                    <tr data-id="<?= $platform['id'] ?>">
                        <td><?= htmlspecialchars($platform['id']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($platform['display_name']) ?></strong>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($platform['name']) ?></code>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $platform['enabled'] ? 'active' : 'inactive' ?>" id="statusBadge-<?= $platform['id'] ?>">
                                <?= $platform['enabled'] ? 'Activa' : 'Inactiva' ?>
                            </span>
                        </td>
                        <td>
                            <label class="toggle-switch" title="<?= $platform['enabled'] ? 'Desactivar' : 'Activar' ?>">
                                <input type="checkbox" class="toggle-input" data-id="<?= $platform['id'] ?>" <?= $platform['enabled'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <?= $platform['created_at'] ? date('d/m/Y H:i', strtotime($platform['created_at'])) : '-' ?>
                        </td>
                        <td>
                            <?= $platform['updated_at'] ? date('d/m/Y H:i', strtotime($platform['updated_at'])) : '-' ?>
                        </td>
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
