<?php
/**
 * GAC - Vista Parcial de Tabla de Cuentas de Email
 * Para actualización AJAX
 */
?>

<div class="table-container">
    <table class="admin-table" id="emailAccountsTable">
        <thead>
            <tr>
                <th class="checkbox-column" style="display: none; width: 40px;">
                    <input type="checkbox" id="selectAll" title="Seleccionar todos">
                </th>
                <th style="width: 60px;">ID</th>
                <th style="width: 25%;">Correo</th>
                <th style="width: 15%;">Usuario (acceso)</th>
                <th style="width: 20%;">Plataforma</th>
                <th style="width: 18%;">Fecha registro</th>
                <th style="width: 150px;">Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if (empty($email_accounts)): ?>
                <tr>
                    <td colspan="7" class="text-center">
                        <p class="empty-message">No hay registros de acceso. Usa "Asignar/Actualizar Usuario" o "Registro masivo" para agregar.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($email_accounts as $account): ?>
                    <?php
                    // Datos desde user_access: email, password (usuario), platform_display_name
                    $usuario = $account['password'] ?? '';
                    $plataforma = $account['platform_display_name'] ?? $account['platform_name'] ?? '—';
                    $fechaRegistro = !empty($account['created_at']) ? date('d/m/Y H:i', strtotime($account['created_at'])) : '—';
                    ?>
                    <tr data-id="<?= (int)$account['id'] ?>" class="table-row">
                        <td class="checkbox-column" style="display: none;">
                            <input type="checkbox" class="row-checkbox" value="<?= (int)$account['id'] ?>">
                        </td>
                        <td><?= (int)$account['id'] ?></td>
                        <td class="email-cell"><?= htmlspecialchars($account['email'] ?? '') ?></td>
                        <td class="user-cell"><?= htmlspecialchars($usuario) ?></td>
                        <td class="platform-cell">
                            <span class="platform-badge"><?= htmlspecialchars($plataforma) ?></span>
                        </td>
                        <td><span class="sync-time"><?= $fechaRegistro ?></span></td>
                        <td class="actions-cell">
                            <button class="btn-icon btn-toggle" 
                                    data-id="<?= (int)$account['id'] ?>"
                                    data-enabled="<?= (int)($account['enabled'] ?? 1) ?>"
                                    title="<?= ($account['enabled'] ?? 1) ? 'Deshabilitar' : 'Habilitar' ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <?php if (!empty($account['enabled'])): ?>
                                        <path d="M18 6L6 18M6 6l12 12"/>
                                    <?php else: ?>
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    <?php endif; ?>
                                </svg>
                            </button>
                            <a href="/admin/user-access?email=<?= rawurlencode($account['email'] ?? '') ?>&platform_id=<?= (int)($account['platform_id'] ?? 0) ?>" 
                               class="btn-icon btn-edit" 
                               title="Editar acceso">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                            <button class="btn-icon btn-delete" 
                                    data-id="<?= (int)$account['id'] ?>"
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

<?php
$total_records = (int)($total_records ?? 0);
$total_pages = (int)($total_pages ?? 1);
$current_page = (int)($current_page ?? 1);
$per_page = (int)($per_page ?? 15);
$from = $total_records === 0 ? 0 : (($current_page - 1) * $per_page) + 1;
$to = min($current_page * $per_page, $total_records);
?>
<div class="pagination-container">
    <div class="pagination-info">
        Mostrando 
        <strong><?= $from ?></strong> 
        - 
        <strong><?= $to ?></strong> 
        de 
        <strong><?= number_format($total_records) ?></strong> 
        registros
    </div>
    <?php if ($total_pages > 1): ?>
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
    <?php endif; ?>
</div>
