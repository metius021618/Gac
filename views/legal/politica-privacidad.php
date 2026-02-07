<?php
/**
 * GAC - Política de Privacidad
 */
ob_start();
?>
<div class="container legal-page">
    <div class="legal-content">
        <h1>Política de Privacidad</h1>
        <p class="legal-updated">Última actualización: <?= date('d/m/Y') ?></p>

        <p>Esta Política de Privacidad describe cómo <strong><?= htmlspecialchars(gac_name()) ?></strong> («nosotros», «el servicio») recopila, usa y protege la información cuando utilizas nuestra aplicación de consulta de códigos de acceso.</p>

        <h2>1. Información que recopilamos</h2>
        <ul>
            <li><strong>Correo electrónico:</strong> El que introduces al consultar códigos o al registrar acceso (Gmail, Outlook, Hotmail u otro).</li>
            <li><strong>Contenido de correos:</strong> Para ofrecer el servicio, el sistema lee correos electrónicos de las cuentas conectadas (vía Gmail API, Microsoft Graph o IMAP) y extrae códigos de acceso y datos asociados (asunto, remitente, cuerpo del mensaje) para mostrarlos en la consulta.</li>
            <li><strong>Datos de acceso:</strong> Usuario/clave que configuras para consultar y, si aplica, tokens OAuth (acceso de solo lectura) para Gmail y Outlook, almacenados de forma segura para mantener la conexión.</li>
        </ul>

        <h2>2. Uso de la información</h2>
        <p>La información se utiliza exclusivamente para:</p>
        <ul>
            <li>Mostrarte los códigos de acceso correspondientes a tu correo y plataforma.</li>
            <li>Gestionar el registro de cuentas (Gmail, Outlook, correo corporativo) y sus permisos de lectura.</li>
            <li>Mantener el funcionamiento técnico del servicio (sincronización de correos, consultas y administración).</li>
        </ul>

        <h2>3. Bases de datos y almacenamiento</h2>
        <p>Los datos se almacenan en bases de datos bajo nuestro control (servidor de hosting). Los correos se leen con permisos de solo lectura (Gmail API / Microsoft Graph / IMAP) y solo se guardan los datos necesarios para el servicio (destinatario, remitente, asunto, cuerpo del mensaje y códigos extraídos).</p>

        <h2>4. Compartir datos</h2>
        <p>No vendemos ni compartimos tu información personal con terceros para fines comerciales. Solo se comparte lo estrictamente necesario con proveedores que permiten el servicio (por ejemplo, Google y Microsoft para OAuth), conforme a sus propias políticas de privacidad.</p>

        <h2>5. Seguridad</h2>
        <p>Aplicamos medidas técnicas y organizativas para proteger los datos (acceso restringido, conexiones seguras, almacenamiento de tokens de forma controlada).</p>

        <h2>6. Tus derechos</h2>
        <p>Puedes solicitar acceso, corrección o eliminación de los datos asociados a tu correo contactando al responsable del servicio. Si conectaste una cuenta Gmail u Outlook, puedes revocar el acceso en cualquier momento desde la configuración de tu cuenta de Google o Microsoft.</p>

        <h2>7. Cambios</h2>
        <p>Podemos actualizar esta política. La fecha de «Última actualización» se modificará y, si el cambio es relevante, te lo haremos saber cuando sea posible.</p>

        <p><a href="/">Volver al inicio</a> &nbsp;|&nbsp; <a href="/condiciones-servicio">Condiciones del Servicio</a></p>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Política de Privacidad';
$show_nav = true;
$show_footer = true;
$footer_text = '';
require base_path('views/layouts/main.php');
