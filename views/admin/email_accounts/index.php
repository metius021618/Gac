<?php
/**
 * GAC - Vista de Listado de Cuentas de Email
 * Con búsqueda, paginación y diseño mejorado
 */

$content = ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">Correos Registrados</h1>
        <a href="/admin/email-accounts/create" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Agregar Cuenta
        </a>
    </div>

    <div class="admin-content">
        <!-- Barra de búsqueda y filtros -->
        <div class="table-controls">
            <div class="search-box">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="search-input" 
                    placeholder="Buscar por correo o usuario..." 
                    value="<?= htmlspecialchars($search ?? '') ?>"
                    autocomplete="off"
                >
                <button class="search-clear" id="clearSearch" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div class="per-page-selector">
                <label for="perPageSelect" class="per-page-label">Mostrar:</label>
                <select id="perPageSelect" class="per-page-select">
                    <option value="15" <?= ($per_page ?? '15') === '15' ? 'selected' : '' ?>>15</option>
                    <option value="30" <?= ($per_page ?? '15') === '30' ? 'selected' : '' ?>>30</option>
                    <option value="60" <?= ($per_page ?? '15') === '60' ? 'selected' : '' ?>>60</option>
                    <option value="100" <?= ($per_page ?? '15') === '100' ? 'selected' : '' ?>>100</option>
                    <option value="all" <?= ($per_page ?? '15') === 'all' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
        </div>

        <!-- Tabla de cuentas -->
        <div class="table-container">
            <table class="admin-table" id="emailAccountsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Correo</th>
                        <th>Usuario</th>
                        <th>Servidor IMAP</th>
                        <th>Puerto</th>
                        <th>Estado</th>
                        <th>Última Sincronización</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($email_accounts)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <p class="empty-message">No hay cuentas de email registradas</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($email_accounts as $account): ?>
                            <tr data-id="<?= $account['id'] ?>" class="table-row">
                                <td><?= $account['id'] ?></td>
                                <td class="email-cell"><?= htmlspecialchars($account['email']) ?></td>
                                <td class="user-cell"><?= htmlspecialchars($account['imap_user'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($account['imap_server'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($account['imap_port'] ?? '993') ?></td>
                                <td>
                                    <span class="status-badge status-<?= $account['enabled'] ? 'active' : 'inactive' ?>">
                                        <?= $account['enabled'] ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($account['last_sync_at']): ?>
                                        <span class="sync-time" title="<?= htmlspecialchars($account['last_sync_at']) ?>">
                                            <?= date('d/m/Y H:i', strtotime($account['last_sync_at'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="sync-time never">Nunca</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button class="btn-icon btn-toggle" 
                                            data-id="<?= $account['id'] ?>"
                                            data-enabled="<?= $account['enabled'] ?>"
                                            title="<?= $account['enabled'] ? 'Deshabilitar' : 'Habilitar' ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <?php if ($account['enabled']): ?>
                                                <path d="M18 6L6 18M6 6l12 12"/>
                                            <?php else: ?>
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            <?php endif; ?>
                                        </svg>
                                    </button>
                                    <a href="/admin/email-accounts/edit?id=<?= $account['id'] ?>" 
                                       class="btn-icon btn-edit" 
                                       title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>
                                    <button class="btn-icon btn-delete" 
                                            data-id="<?= $account['id'] ?>"
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

        <!-- Paginación -->
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando 
                <strong><?= (($pagination['page'] - 1) * $pagination['per_page']) + 1 ?></strong> 
                - 
                <strong><?= min($pagination['page'] * $pagination['per_page'], $pagination['total']) ?></strong> 
                de 
                <strong><?= number_format($pagination['total']) ?></strong> 
                registros
            </div>
            <div class="pagination-controls">
                <button class="pagination-btn" 
                        data-page="<?= $pagination['page'] - 1 ?>" 
                        <?= $pagination['page'] <= 1 ? 'disabled' : '' ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    Anterior
                </button>
                
                <div class="pagination-pages">
                    <?php
                    $currentPage = $pagination['page'];
                    $totalPages = $pagination['total_pages'];
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    if ($startPage > 1): ?>
                        <button class="pagination-page" data-page="1">1</button>
                        <?php if ($startPage > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <button class="pagination-page <?= $i === $currentPage ? 'active' : '' ?>" 
                                data-page="<?= $i ?>">
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <button class="pagination-page" data-page="<?= $totalPages ?>">
                            <?= $totalPages ?>
                        </button>
                    <?php endif; ?>
                </div>
                
                <button class="pagination-btn" 
                        data-page="<?= $pagination['page'] + 1 ?>" 
                        <?= $pagination['page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>>
                    Siguiente
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Correos Registrados';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/email_accounts.css'];
$additional_js = ['/assets/js/admin/email_accounts.js'];

require base_path('views/layouts/main.php');
?>
