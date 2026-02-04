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
            redirect('/admin/user-access');
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

        if ($error) {
            $_SESSION['gmail_error'] = $error === 'access_denied'
                ? 'Has cancelado la autorización de Gmail.'
                : 'Error de Google: ' . htmlspecialchars($error);
            redirect('/admin/user-access');
            return;
        }

        if (empty($code)) {
            $_SESSION['gmail_error'] = 'No se recibió el código de autorización.';
            redirect('/admin/user-access');
            return;
        }

        if (!defined('GMAIL_CLIENT_ID') || !GMAIL_CLIENT_ID || !defined('GMAIL_CLIENT_SECRET') || !GMAIL_CLIENT_SECRET) {
            $_SESSION['gmail_error'] = 'Gmail API no está configurada.';
            redirect('/admin/user-access');
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
            redirect('/admin/user-access');
            return;
        }

        if (isset($token['error'])) {
            $_SESSION['gmail_error'] = 'Error de Google: ' . ($token['error_description'] ?? $token['error']);
            redirect('/admin/user-access');
            return;
        }

        $client->setAccessToken($token);
        $accessToken = $token['access_token'] ?? '';
        $refreshToken = $token['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            $_SESSION['gmail_error'] = 'No se recibió refresh token. Asegúrate de usar access_type=offline y prompt=consent.';
            redirect('/admin/user-access');
            return;
        }

        try {
            $gmail = new Gmail($client);
            $profile = $gmail->users->getProfile('me');
            $email = $profile->getEmailAddress();
        } catch (\Exception $e) {
            $_SESSION['gmail_error'] = 'Error al obtener perfil de Gmail: ' . $e->getMessage();
            redirect('/admin/user-access');
            return;
        }

        if (empty($email)) {
            $_SESSION['gmail_error'] = 'No se pudo obtener el correo de la cuenta Gmail.';
            redirect('/admin/user-access');
            return;
        }

        $id = $this->emailAccountRepository->createOrUpdateGmailAccount($email, $accessToken, $refreshToken);
        if ($id === false) {
            $_SESSION['gmail_error'] = 'Error al guardar la cuenta en la base de datos.';
            redirect('/admin/user-access');
            return;
        }

        // Registrar también en user_access para que aparezca en "Correos Registrados"
        $platforms = $this->platformRepository->findAllEnabled();
        $firstPlatformId = !empty($platforms) ? (int) $platforms[0]['id'] : 0;
        if ($firstPlatformId > 0) {
            $this->userAccessRepository->createOrUpdate($email, 'Gmail (OAuth)', $firstPlatformId);
        }

        unset($_SESSION['gmail_error']);
        $_SESSION['gmail_success'] = 'Cuenta Gmail conectada correctamente: ' . htmlspecialchars($email);
        redirect('/admin/user-access');
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
