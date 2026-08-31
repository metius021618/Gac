<?php
/**
 * GAC - Vista consulta Hogar / Actualizar hogar (tabs sin recarga)
 */

$initial_mode = ($initial_mode ?? 'hogar') === 'viaje' ? 'viaje' : 'hogar';
$card_title_hogar = 'Consulta tu código Netflix';
$card_title_viaje = 'Actualizar hogar';

$content = ob_start();
?>

<div class="consult-container">
    <div class="consult-wrapper">
        <div class="logo-section">
            <div class="consult-logo-wrap">
                <img src="/assets/imagenes/logogato.jpeg" alt="GAC" class="consult-logo">
            </div>
        </div>

        <div class="consult-card">
            <div class="card-header">
                <h1 class="card-title" id="consultCardTitle"><?= htmlspecialchars($initial_mode === 'viaje' ? $card_title_viaje : $card_title_hogar) ?></h1>
            </div>

            <div class="hogar-mode-switch" data-active="<?= htmlspecialchars($initial_mode) ?>" data-initial-mode="<?= htmlspecialchars($initial_mode) ?>">
                <div class="hogar-mode-track" role="tablist" aria-label="Modo de consulta">
                    <span class="hogar-mode-slider" aria-hidden="true"></span>
                    <button type="button" class="hogar-mode-option <?= $initial_mode === 'hogar' ? 'is-active' : '' ?>" data-mode="hogar" role="tab" aria-selected="<?= $initial_mode === 'hogar' ? 'true' : 'false' ?>">Código temporal</button>
                    <button type="button" class="hogar-mode-option <?= $initial_mode === 'viaje' ? 'is-active' : '' ?>" data-mode="viaje" role="tab" aria-selected="<?= $initial_mode === 'viaje' ? 'true' : 'false' ?>">Actualizar hogar</button>
                </div>
            </div>

            <div class="hogar-note">
                <strong>⚠️ Nota:</strong><br>
                Espera 5–15 segundos desde el envío del código antes de consultar el correo.<br>
                Si no llega el correo, puedes solicitar el código nuevamente.
            </div>

            <form id="consultForm" class="consult-form" novalidate>
                <div class="form-group">
                    <label for="email" class="form-label">
                        <svg class="form-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Correo
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="tu@correo.com"
                        required
                        autocomplete="email"
                    >
                    <span class="form-error" id="emailError"></span>
                </div>

                <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                    <span class="btn-text">Consultar</span>
                    <span class="btn-loader" id="btnLoader">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 11-6.219-8.56"/>
                        </svg>
                    </span>
                </button>
            </form>
        </div>

        <div id="emailModal" class="email-modal hidden">
            <div class="email-modal-overlay"></div>
            <div class="email-modal-container">
                <div class="email-modal-header">
                    <h2 class="email-modal-title">Email Completo</h2>
                    <button type="button" class="email-modal-close" id="closeEmailModal" aria-label="Cerrar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="email-modal-content" id="emailModalContent">
                    <div class="email-info">
                        <div class="email-info-row">
                            <span class="email-info-label">De:</span>
                            <span class="email-info-value" id="emailModalFrom">-</span>
                        </div>
                        <div class="email-info-row">
                            <span class="email-info-label">Asunto:</span>
                            <span class="email-info-value" id="emailModalSubject">-</span>
                        </div>
                        <div class="email-info-row">
                            <span class="email-info-label">Fecha:</span>
                            <span class="email-info-value" id="emailModalDate">-</span>
                        </div>
                    </div>
                    <div class="email-body-container">
                        <div class="email-body" id="emailModalBody"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$title = $title ?? ($initial_mode === 'viaje' ? 'Actualizar hogar' : 'Consulta tu código Netflix');
$description = 'Consulta tu código temporal. Espera 5–15 segundos desde el envío del código antes de consultar.';
$show_nav = false;
$footer_text = 'Tienes alguna duda, comunicate conmigo';
$footer_contact = false;
$footer_whatsapp = true;
$footer_whatsapp_text = 'Hola, tengo una duda';
$additional_css = ['/assets/css/consult.css'];
$additional_js = ['/assets/js/hogar-mode-switch.js', '/assets/js/hogar-consult.js'];

require base_path('views/layouts/main.php');
?>
