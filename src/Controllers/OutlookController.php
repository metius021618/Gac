<?php
/**
 * GAC - Controlador Outlook/Microsoft Graph OAuth
 * Conectar cuentas Outlook/Hotmail mediante OAuth 2.0 y guardar tokens para el cron
 *
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\EmailAccountRepository;
use Gac\Repositories\UserAccessRepository;
use Gac\Repositories\PlatformRepository;

class OutlookController
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
     * Redirigir al usuario a la pantalla de autorización de Microsoft
     */
    public function connect(Request $request): void
    {
        if (!defined('OUTLOOK_CLIENT_ID') || !OUTLOOK_CLIENT_ID || !defined('OUTLOOK_CLIENT_SECRET') || !OUTLOOK_CLIENT_SECRET) {
            $_SESSION['outlook_error'] = 'Outlook API no está configurada. Añade OUTLOOK_CLIENT_ID, OUTLOOK_CLIENT_SECRET y OUTLOOK_TENANT_ID en .env';
            redirect('/admin/user-access');
            return;
        }

        $tenantId = defined('OUTLOOK_TENANT_ID') && OUTLOOK_TENANT_ID ? OUTLOOK_TENANT_ID : 'common';
        $redirectUri = defined('OUTLOOK_REDIRECT_URI') ? OUTLOOK_REDIRECT_URI : $this->buildRedirectUri();
        
        $scopes = 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Read offline_access';
        // prompt=consent obliga a Microsoft a mostrar la pantalla de permisos (incl. "Leer tu correo")
        // Sin esto, puede reutilizar un consentimiento antiguo sin Mail.Read y el token falla en el cron
        $authUrl = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?client_id=%s&response_type=code&redirect_uri=%s&response_mode=query&scope=%s&prompt=consent&state=%s',
            urlencode($tenantId),
            urlencode(OUTLOOK_CLIENT_ID),
            urlencode($redirectUri),
            urlencode($scopes),
            urlencode(bin2hex(random_bytes(16)))
        );

        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

    /**
     * Callback de Microsoft OAuth: intercambiar code por tokens y guardar cuenta
     */
    public function callback(Request $request): void
    {
        $code = $_GET['code'] ?? null;
        $error = $_GET['error'] ?? null;
        $errorDescription = $_GET['error_description'] ?? null;

        if ($error) {
            $_SESSION['outlook_error'] = $error === 'access_denied'
                ? 'Has cancelado la autorización de Outlook.'
                : 'Error de Microsoft: ' . htmlspecialchars($errorDescription ?? $error);
            redirect('/admin/user-access');
            return;
        }

        if (empty($code)) {
            $_SESSION['outlook_error'] = 'No se recibió el código de autorización.';
            redirect('/admin/user-access');
            return;
        }

        if (!defined('OUTLOOK_CLIENT_ID') || !OUTLOOK_CLIENT_ID || !defined('OUTLOOK_CLIENT_SECRET') || !OUTLOOK_CLIENT_SECRET) {
            $_SESSION['outlook_error'] = 'Outlook API no está configurada.';
            redirect('/admin/user-access');
            return;
        }

        $tenantId = defined('OUTLOOK_TENANT_ID') && OUTLOOK_TENANT_ID ? OUTLOOK_TENANT_ID : 'common';
        $redirectUri = defined('OUTLOOK_REDIRECT_URI') ? OUTLOOK_REDIRECT_URI : $this->buildRedirectUri();

        // Intercambiar code por tokens
        $tokenUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $tenantId);
        $tokenData = [
            'client_id' => OUTLOOK_CLIENT_ID,
            'client_secret' => OUTLOOK_CLIENT_SECRET,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
            'scope' => 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Read offline_access'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $_SESSION['outlook_error'] = 'Error al obtener tokens de Microsoft (HTTP ' . $httpCode . ').';
            redirect('/admin/user-access');
            return;
        }

        $token = json_decode($response, true);
        if (isset($token['error'])) {
            $_SESSION['outlook_error'] = 'Error de Microsoft: ' . ($token['error_description'] ?? $token['error']);
            redirect('/admin/user-access');
            return;
        }

        $accessToken = $token['access_token'] ?? '';
        $refreshToken = $token['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            $_SESSION['outlook_error'] = 'No se recibió refresh token. Asegúrate de incluir offline_access en los scopes.';
            redirect('/admin/user-access');
            return;
        }

        // Obtener email del usuario desde Microsoft Graph
        $graphUrl = 'https://graph.microsoft.com/v1.0/me';
        $ch = curl_init($graphUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        $profileResponse = curl_exec($ch);
        $profileHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($profileHttpCode !== 200) {
            $_SESSION['outlook_error'] = 'Error al obtener perfil de Outlook (HTTP ' . $profileHttpCode . ').';
            redirect('/admin/user-access');
            return;
        }

        $profile = json_decode($profileResponse, true);
        $email = $profile['mail'] ?? $profile['userPrincipalName'] ?? '';
        $email = $this->normalizeOutlookEmail($email);

        if (empty($email)) {
            $_SESSION['outlook_error'] = 'No se pudo obtener el correo de la cuenta Outlook.';
            redirect('/admin/user-access');
            return;
        }

        $id = $this->emailAccountRepository->createOrUpdateOutlookAccount($email, $accessToken, $refreshToken);
        if ($id === false) {
            $_SESSION['outlook_error'] = 'Error al guardar la cuenta en la base de datos.';
            redirect('/admin/user-access');
            return;
        }

        // Registrar también en user_access para que aparezca en "Correos Registrados"
        $platforms = $this->platformRepository->findAllEnabled();
        $firstPlatformId = !empty($platforms) ? (int) $platforms[0]['id'] : 0;
        if ($firstPlatformId > 0) {
            $this->userAccessRepository->createOrUpdate($email, 'Outlook (OAuth)', $firstPlatformId);
        }

        unset($_SESSION['outlook_error']);
        $_SESSION['outlook_success'] = 'Cuenta Outlook conectada correctamente: ' . htmlspecialchars($email);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        redirect('/admin/user-access?outlook_connected=1');
    }

    /**
     * Normalizar email cuando Microsoft devuelve formato guest (ej. apipocoyoni_outlook.com#EXT#@tenant.onmicrosoft.com)
     * a email real: apipocoyoni@outlook.com
     */
    private function normalizeOutlookEmail(string $email): string
    {
        $email = trim($email);
        if (str_contains($email, '#EXT#')) {
            $email = trim(explode('#EXT#', $email)[0]);
            // Reemplazar última barra baja por @ (ej. apipocoyoni_outlook.com -> apipocoyoni@outlook.com)
            $pos = strrpos($email, '_');
            if ($pos !== false) {
                $email = substr($email, 0, $pos) . '@' . substr($email, $pos + 1);
            }
        }
        return strtolower($email);
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
        return $scheme . '://' . $host . $base . '/outlook/callback';
    }
}
