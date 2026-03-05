<?php
/**
 * GAC - Dashboard de Revendedor
 */

$content = ob_start();
?>

<div class="revendedor-container">
    <div class="revendedor-header">
        <h1 class="revendedor-title">Panel</h1>
        <p class="revendedor-subtitle">
            Bienvenido, <?= htmlspecialchars($revendedor['username'] ?? '') ?>. Aquí puedes gestionar tus cuentas vendidas y los accesos de tus clientes.
        </p>
    </div>

    <div class="revendedor-dashboard-cards">
        <a href="/revendedor/lista-cuentas" class="revendedor-card">
            <div class="revendedor-card-icon">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
            </div>
            <div class="revendedor-card-content">
                <h2 class="revendedor-card-title">Lista de cuentas</h2>
                <p class="revendedor-card-text">Ver solo las cuentas que se te han asignado/vendido.</p>
            </div>
        </a>

        <a href="/revendedor/accesos" class="revendedor-card">
            <div class="revendedor-card-icon">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="revendedor-card-content">
                <h2 class="revendedor-card-title">Accesos</h2>
                <p class="revendedor-card-text">Asignar usuarios adicionales a tus correos para consulta de códigos.</p>
            </div>
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? 'Panel de Revendedor';
$show_nav = true;
$hide_main_nav_links = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/revendedor.css'];
$additional_js = ['/assets/js/revendedor.js'];

require base_path('views/layouts/main.php');
