<?php
/**
 * GAC - Vista de Accesos del Revendedor
 * Formulario para crear usuarios adicionales asociados a sus propias cuentas.
 */

$content = ob_start();
?>

<div class="revendedor-container">
    <div class="revendedor-header">
        <h1 class="revendedor-title">Accesos</h1>
        <p class="revendedor-subtitle">
            Crea usuarios adicionales vinculados a tus correos para que tus clientes puedan consultar sus códigos.
        </p>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="revendedor-flash revendedor-flash-<?= $flash_type === 'error' ? 'error' : 'success' ?>">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <div class="revendedor-form-wrapper">
        <form method="post" action="/revendedor/accesos" class="revendedor-form">
            <div class="revendedor-form-group">
                <label for="emailSelect">Correo</label>
                <select id="emailSelect" name="email" required>
                    <option value="">Selecciona un correo</option>
                    <?php foreach ($email_map as $email => $info): ?>
                        <option value="<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="revendedor-form-group">
                <label for="platformSelect">Plataforma</label>
                <select id="platformSelect" name="platform_id" required>
                    <option value="">SELECCIONE PLATAFORMA</option>
                </select>
            </div>

            <div class="revendedor-form-group">
                <label for="subUsernameInput">Usuario adicional</label>
                <input type="text" id="subUsernameInput" name="sub_username" minlength="3" required placeholder="Usuario que usará tu cliente para consultar el código">
            </div>

            <button type="submit" class="revendedor-submit-btn">Guardar acceso</button>
        </form>
    </div>
</div>

<script>
window.REV_EMAIL_PLATFORM_MAP = <?= json_encode(array_values($email_map) ? array_values($email_map) : []) ?>;
</script>

<?php
$content = ob_get_clean();

$title = $title ?? 'Accesos';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css'];
$additional_js = ['/assets/js/revendedor.js'];

require base_path('views/layouts/main.php');

