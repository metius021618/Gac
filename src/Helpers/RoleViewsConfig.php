<?php
/**
 * GAC - Configuración de vistas personalizables por rol
 * Orden y etiquetas de las secciones que un rol puede ver
 */

namespace Gac\Helpers;

class RoleViewsConfig
{
    /** @var array key => ['label' => string, 'nav_label' => string, 'url' => string] */
    private static array $views = [
        'dashboard' => [
            'label' => 'Dashboard',
            'nav_label' => 'Dashboard',
            'url' => '/admin/dashboard',
        ],
        'listar_correos' => [
            'label' => 'Listar correos',
            'nav_label' => 'Correos',
            'url' => '/admin/email-accounts',
        ],
        'registro_acceso' => [
            'label' => 'Registro de acceso',
            'nav_label' => 'Registro de acceso',
            'url' => '/admin/user-access',
        ],
        'registro_masivo' => [
            'label' => 'Registro masivo',
            'nav_label' => 'Registro masivo',
            'url' => '/admin/email-accounts/bulk-register',
        ],
        'registro_asuntos' => [
            'label' => 'Registro de asuntos',
            'nav_label' => 'Registro de asuntos',
            'url' => '/admin/email-subjects',
        ],
        'listar_gmail' => [
            'label' => 'Listar Gmail',
            'nav_label' => 'Gmail',
            'url' => '/admin/email-accounts?filter=gmail',
        ],
        'listar_outlook' => [
            'label' => 'Listar Outlook',
            'nav_label' => 'Outlook',
            'url' => '/admin/email-accounts?filter=outlook',
        ],
        'listar_pocoyoni' => [
            'label' => 'Listar Pocoyoni',
            'nav_label' => 'Pocoyoni',
            'url' => '/admin/email-accounts?filter=pocoyoni',
        ],
        'plataformas_activas' => [
            'label' => 'Plataformas activas',
            'nav_label' => 'Plataformas',
            'url' => '/admin/platforms',
        ],
        'administradores' => [
            'label' => 'Administradores',
            'nav_label' => 'Administradores',
            'url' => '/admin/administrators',
        ],
    ];

    /**
     * Lista de todas las vistas en orden (para checkboxes y preview)
     * @return array [['key' => string, 'label' => string, 'nav_label' => string, 'url' => string], ...]
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::$views as $key => $v) {
            $out[] = [
                'key' => $key,
                'label' => $v['label'],
                'nav_label' => $v['nav_label'],
                'url' => $v['url'],
            ];
        }
        return $out;
    }

    /**
     * Obtener una vista por key
     */
    public static function get(string $key): ?array
    {
        if (!isset(self::$views[$key])) {
            return null;
        }
        return array_merge(['key' => $key], self::$views[$key]);
    }

    /**
     * Keys válidos
     * @return string[]
     */
    public static function keys(): array
    {
        return array_keys(self::$views);
    }

    /**
     * Obtener view_keys requeridos para una ruta/path de admin.
     * Usuario debe tener al menos uno de los keys retornados.
     * @param string $path Ej: /admin/dashboard, /admin/email-accounts?filter=gmail
     * @return string[]|null Array de view_keys requeridos, o null si la ruta no está restringida
     */
    public static function getViewKeysForPath(string $path): ?array
    {
        $path = strtok($path, '?');
        $path = rtrim($path, '/');
        $map = [
            '/admin/dashboard' => ['dashboard'],
            '/admin/email-accounts' => ['listar_correos', 'listar_gmail', 'listar_outlook', 'listar_pocoyoni'],
            '/admin/user-access' => ['registro_acceso'],
            '/admin/email-accounts/bulk-register' => ['registro_masivo'],
            '/admin/email-subjects' => ['registro_asuntos'],
            '/admin/platforms' => ['plataformas_activas'],
            '/admin/administrators' => ['administradores'],
            '/admin/users' => ['administradores'],
        ];
        if (isset($map[$path])) {
            return $map[$path];
        }
        if (strpos($path, '/admin/email-accounts') === 0) {
            return ['listar_correos', 'listar_gmail', 'listar_outlook', 'listar_pocoyoni'];
        }
        return null;
    }
}
