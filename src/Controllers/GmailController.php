<?php
/**
 * GAC - Controlador Gmail OAuth
 * Conectar cuentas Gmail mediante OAuth 2.0 y guardar tokens para el cron
 *
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Gac\Core\Request;
use Gac\Repositories\EmailAccountRepository;
use Gac\Repositories\UserAccessRepository;
use Gac\Repositories\PlatformRepository;

class GmailController
{
    private EmailAccountRepository $emailAccountRepository;
    private UserAccessRepository $userAccessRepository;
    private PlatformRepository $platformRepository;

    public function __construct()
    {
        $this->emailAccountRepository = new EmailAccountRepository();
        $this->userAccessRepository = new UserAccessRepository();
        $this->platformRepository = new PlatformRepository();
    }

    /**
     * Redirigir al usuario a la pantalla de autorización de Google
     */
    public function connect(Request $request): void
    {
        if (!defined('GMAIL_CLIENT_ID') || !GMAIL_CLIENT_ID || !defined('GMAIL_CLIENT_SECRET') || !GMAIL_CLIENT_SECRET) {
            $_SESSION['gmail_error'] = 'Gmail API no está configurada. Añade GMAIL_CLIENT_ID y GMAIL_CLIENT_SECRET en .env';
            redirect($request->get('from') === 'settings' ? '/admin/settings' : '/admin/email-accounts?filter=gmail');
            return;
        }

        $redirectUri = defined('GMAIL_REDIRECT_URI') ? GMAIL_REDIRECT_URI : '';
        if (empty($redirectUri)) {
            $redirectUri = $this->buildRedirectUri();
        }

        $client = new GoogleClient();
        $client->setClientId(GMAIL_CLIENT_ID);
        $client->setClientSecret(GMAIL_CLIENT_SECRET);
        $client->setRedirectUri($redirectUri);
        $client->addScope(Gmail::GMAIL_READONLY);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        if ($request->get('from') === 'settings') {
            $client->setState('from=settings');
        }

        $authUrl = $client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

    /**
     * Callback de Google OAuth: intercambiar code por tokens y guardar cuenta
     */
    public function callback(Request $request): void
    {
        $code = $_GET['code'] ?? null;
        $error = $_GET['error'] ?? null;
        $state = $_GET['state'] ?? '';
        $fromSettings = ($state === 'from=settings');
        $redirectAfter = $fromSettings ? '/admin/settings' : '/admin/email-accounts?filter=gmail';

        if ($error) {
            $_SESSION['gmail_error'] = $error === 'access_denied'
                ? 'Has cancelado la autorización de Gmail.'
                : 'Error de Google: ' . htmlspecialchars($error);
            redirect($redirectAfter);
            return;
        }

        if (empty($code)) {
            $_SESSION['gmail_error'] = 'No se recibió el código de autorización.';
            redirect($redirectAfter);
            return;
        }

        if (!defined('GMAIL_CLIENT_ID') || !GMAIL_CLIENT_ID || !defined('GMAIL_CLIENT_SECRET') || !GMAIL_CLIENT_SECRET) {
            $_SESSION['gmail_error'] = 'Gmail API no está configurada.';
            redirect($redirectAfter);
            return;
        }

        $redirectUri = defined('GMAIL_REDIRECT_URI') ? GMAIL_REDIRECT_URI : $this->buildRedirectUri();
        $client = new GoogleClient();
        $client->setClientId(GMAIL_CLIENT_ID);
        $client->setClientSecret(GMAIL_CLIENT_SECRET);
        $client->setRedirectUri($redirectUri);
        $client->addScope(Gmail::GMAIL_READONLY);

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);
        } catch (\Exception $e) {
            $_SESSION['gmail_error'] = 'Error al obtener tokens: ' . $e->getMessage();
            redirect($redirectAfter);
            return;
        }

        if (isset($token['error'])) {
            $_SESSION['gmail_error'] = 'Error de Google: ' . ($token['error_description'] ?? $token['error']);
            redirect($redirectAfter);
            return;
        }

        $client->setAccessToken($token);
        $accessToken = $token['access_token'] ?? '';
        $refreshToken = $token['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            $_SESSION['gmail_error'] = 'No se recibió refresh token. Asegúrate de usar access_type=offline y prompt=consent.';
            redirect($redirectAfter);
            return;
        }

        try {
            $gmail = new Gmail($client);
            $profile = $gmail->users->getProfile('me');
            $email = $profile->getEmailAddress();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '429') !== false || stripos($msg, 'rate limit') !== false || stripos($msg, 'RESOURCE_EXHAUSTED') !== false) {
                $_SESSION['gmail_error'] = 'Límite de solicitudes de Google alcanzado. Espera 5-10 minutos y vuelve a intentar configurar la cuenta Gmail.';
            } else {
                $_SESSION['gmail_error'] = 'Error al obtener perfil de Gmail: ' . $msg;
            }
            redirect($redirectAfter);
            return;
        }

        if (empty($email)) {
            $_SESSION['gmail_error'] = 'No se pudo obtener el correo de la cuenta Gmail.';
            redirect($redirectAfter);
            return;
        }

        $id = $this->emailAccountRepository->createOrUpdateGmailAccount($email, $accessToken, $refreshToken);
        if ($id === false) {
            $_SESSION['gmail_error'] = 'Error al guardar la cuenta en la base de datos.';
            redirect($redirectAfter);
            return;
        }

        if ($fromSettings) {
            // Cuenta matriz: solo se guarda en gmail_matrix; no se registra en user_access ni se usa email_accounts como "matriz"
            $this->emailAccountRepository->setGmailMatrixAccount((int) $id);
            $this->emailAccountRepository->deleteOtherGmailAccountsExcept((int) $id);
        } else {
            // Conectar desde listado de correos: sí registrar en user_access para que aparezca en Correos Registrados
            $platforms = $this->platformRepository->findAllEnabled();
            $firstPlatformId = !empty($platforms) ? (int) $platforms[0]['id'] : 0;
            if ($firstPlatformId > 0) {
                $this->userAccessRepository->createOrUpdate($email, 'Gmail (OAuth)', $firstPlatformId);
            }
        }

        unset($_SESSION['gmail_error']);
        $_SESSION['gmail_success'] = 'Cuenta Gmail matriz configurada correctamente: ' . htmlspecialchars($email) . '. El cron la leerá automáticamente.';
        redirect($redirectAfter);
    }

    /**
     * Construir redirect_uri desde la petición actual (fallback si no está en .env)
     */
    private function buildRedirectUri(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base = dirname($script);
        if ($base === '/' || $base === '\\') {
            $base = '';
        }
        return $scheme . '://' . $host . $base . '/gmail/callback';
    }
}
