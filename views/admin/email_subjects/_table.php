<?php
/**
 * GAC - Vista Parcial de Tabla de Asuntos de Email
 * Para actualización AJAX
 */
?>

<div class="table-container">
    <table class="admin-table" id="emailSubjectsTable">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>Plataforma</th>
                <th>Asunto</th>
                <th style="width: 150px;">Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if (empty($subjects)): ?>
                <tr>
                    <td colspan="4" class="text-center">
                        <p class="empty-message">No hay asuntos registrados</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($subjects as $subject): ?>
                    <tr data-id="<?= $subject['id'] ?>" class="table-row">
                        <td><?= $subject['id'] ?></td>
                        <td class="platform-cell">
                            <span class="platform-badge"><?= htmlspecialchars($subject['platform_display_name'] ?? $subject['platform_name']) ?></span>
                        </td>
                        <td class="subject-cell"><?= htmlspecialchars($subject['subject_line']) ?></td>
                        <td class="actions-cell">
                            <button type="button"
                                    class="btn-icon btn-edit" 
                                    data-id="<?= $subject['id'] ?>"
                                    data-platform-id="<?= $subject['platform_id'] ?>"
                                    data-subject-line="<?= htmlspecialchars($subject['subject_line']) ?>"
                                    title="Editar">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="btn-icon btn-delete" 
                                    data-id="<?= $subject['id'] ?>"
                                    data-subject="<?= htmlspecialchars($subject['subject_line']) ?>"
                                    title="Eliminar">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
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
        Mostrando 
        <strong><?= (($current_page - 1) * $per_page) + 1 ?></strong> 
        - 
        <strong><?= min($current_page * $per_page, $total_records) ?></strong> 
        de 
        <strong><?= number_format($total_records) ?></strong> 
        registros
    </div>
    <div class="pagination-controls">
        <button class="pagination-btn" 
                data-page="<?= $current_page - 1 ?>" 
                <?= $current_page <= 1 ? 'disabled' : '' ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Anterior
        </button>
        
        <div class="pagination-pages">
            <?php
            $startPage = max(1, $current_page - 2);
            $endPage = min($total_pages, $current_page + 2);
            
            if ($startPage > 1): ?>
                <button class="pagination-page" data-page="1">1</button>
                <?php if ($startPage > 2): ?>
                    <span class="pagination-ellipsis">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <button class="pagination-page <?= $i === $current_page ? 'active' : '' ?>" 
                        data-page="<?= $i ?>">
                    <?= $i ?>
                </button>
            <?php endfor; ?>
            
            <?php if ($endPage < $total_pages): ?>
                <?php if ($endPage < $total_pages - 1): ?>
                    <span class="pagination-ellipsis">...</span>
                <?php endif; ?>
                <button class="pagination-page" data-page="<?= $total_pages ?>">
                    <?= $total_pages ?>
                </button>
            <?php endif; ?>
        </div>
        
        <button class="pagination-btn" 
                data-page="<?= $current_page + 1 ?>" 
                <?= $current_page >= $total_pages ? 'disabled' : '' ?>>
            Siguiente
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
    </div>
</div>
<?php endif; ?>
