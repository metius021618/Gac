<?php
/**
 * GAC - Condiciones del Servicio
 */
ob_start();
?>
<div class="container legal-page">
    <div class="legal-content">
        <h1>Condiciones del Servicio</h1>
        <p class="legal-updated">Última actualización: <?= date('d/m/Y') ?></p>

        <p>Al utilizar el servicio de <strong><?= htmlspecialchars(gac_name()) ?></strong> («el Servicio») aceptas las siguientes condiciones.</p>

        <h2>1. Descripción del servicio</h2>
        <p>El Servicio permite consultar códigos de acceso (por ejemplo, de plataformas de streaming o servicios digitales) asociados a tu correo electrónico. Para ello, el sistema puede leer correos de las cuentas que conectes (Gmail, Outlook u otras vía IMAP) con permisos de solo lectura, extraer códigos y mostrarlos de forma segura en la aplicación.</p>

        <h2>2. Uso aceptable</h2>
        <p>Te comprometes a:</p>
        <ul>
            <li>Utilizar el Servicio solo para fines lícitos y de acuerdo con las leyes aplicables.</li>
            <li>No usar el Servicio para acceder a cuentas o datos ajenos sin autorización.</li>
            <li>Mantener la confidencialidad de tus credenciales y de la clave de acceso que configures para la consulta.</li>
        </ul>

        <h2>3. Cuentas conectadas (Gmail, Outlook, etc.)</h2>
        <p>Si conectas una cuenta de correo (por ejemplo mediante «Conectar Gmail» o «Conectar Outlook»), autorizas al Servicio a leer los correos de esa cuenta en modo solo lectura para ofrecer la funcionalidad de consulta de códigos. Puedes revocar ese acceso en cualquier momento desde la configuración de tu cuenta de Google o Microsoft, o desde el panel de administración del Servicio cuando corresponda.</p>

        <h2>4. Disponibilidad y modificaciones</h2>
        <p>El Servicio se ofrece «tal cual». Nos reservamos el derecho de modificar, suspender o discontinuar funciones con previo aviso cuando sea razonable. No garantizamos disponibilidad ininterrumpida.</p>

        <h2>5. Limitación de responsabilidad</h2>
        <p>En la medida permitida por la ley, el Servicio y sus responsables no serán responsables por daños indirectos, pérdida de datos o perjuicios derivados del uso o la imposibilidad de usar el Servicio. El uso del Servicio es bajo tu propia cuenta y riesgo.</p>

        <h2>6. Propiedad intelectual</h2>
        <p>El software, la marca y los contenidos propios del Servicio son de su titular. No se concede ninguna licencia sobre ellos más allá del uso necesario para utilizar el Servicio.</p>

        <h2>7. Terminación</h2>
        <p>Podemos suspender o dar por terminado tu acceso al Servicio si se incumplen estas condiciones o por razones de seguridad o operativas. Tú puedes dejar de usar el Servicio y revocar los permisos de tus cuentas conectadas en cualquier momento.</p>

        <h2>8. Ley aplicable</h2>
        <p>Estas condiciones se rigen por las leyes del país desde el que se presta el Servicio. Cualquier disputa se resolverá en los tribunales competentes de dicho país.</p>

        <h2>9. Contacto</h2>
        <p>Para dudas sobre estas condiciones o sobre el Servicio, utiliza los canales de contacto indicados en la aplicación (por ejemplo, WhatsApp o correo de soporte).</p>

        <p><a href="/">Volver al inicio</a> &nbsp;|&nbsp; <a href="/politica-privacidad">Política de Privacidad</a></p>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Condiciones del Servicio';
$show_nav = true;
$show_footer = true;
$footer_text = '';
require base_path('views/layouts/main.php');
