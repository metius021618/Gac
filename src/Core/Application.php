<?php
/**
 * GAC - Núcleo de la Aplicación
 * 
 * @package Gac\Core
 */

namespace Gac\Core;

class Application
{
    /**
     * Ejecutar la aplicación
     */
    public function run(): void
    {
        // Inicializar router
        $router = new Router();
        
        // Cargar rutas
        $this->loadRoutes($router);
        
        // Procesar request
        $router->dispatch();
    }
    
    /**
     * Cargar rutas de la aplicación
     */
    private function loadRoutes(Router $router): void
    {
        // Rutas públicas
        $router->get('/', 'CodeController@consult');
        $router->post('/codes/consult', 'CodeController@consult');
        
        // Rutas API
        $router->post('/api/v1/codes/consult', 'CodeController@apiConsult');
        $router->post('/api/v1/codes/consult-by-domain', 'ApiController@consultByDomain');
        $router->get('/api/v1/codes/list-by-domain', 'ApiController@listByDomain');
        
        // Rutas de gestión de cuentas de email (requieren autenticación)
        $router->get('/admin/email-accounts', 'EmailAccountController@index', ['auth']);
        $router->get('/admin/email-accounts/create', 'EmailAccountController@create', ['auth']);
        $router->post('/admin/email-accounts', 'EmailAccountController@store', ['auth']);
        $router->get('/admin/email-accounts/edit', 'EmailAccountController@edit', ['auth']);
        $router->post('/admin/email-accounts/update', 'EmailAccountController@update', ['auth']);
        $router->post('/admin/email-accounts/delete', 'EmailAccountController@destroy', ['auth']);
        $router->post('/admin/email-accounts/toggle-status', 'EmailAccountController@toggleStatus', ['auth']);
        
        // Rutas de autenticación
        $router->get('/login', 'AuthController@showLogin');
        $router->post('/login', 'AuthController@login');
        $router->get('/logout', 'AuthController@logout');
        $router->post('/logout', 'AuthController@logout');
        
        // Rutas administrativas (requieren autenticación)
        $router->get('/admin/dashboard', 'DashboardController@index', ['auth']);
        $router->get('/admin/codes', 'CodeController@index', ['auth']);
        $router->get('/admin/users', 'UserController@index', ['auth']);
        $router->get('/admin/settings', 'SettingsController@index', ['auth']);
        
        // Rutas de plataformas
        $router->get('/admin/platforms', 'PlatformController@index', ['auth']);
        
        // Rutas de administradores
        $router->get('/admin/administrators', 'AdminController@index', ['auth']);
        $router->get('/admin/administrators/edit', 'AdminController@edit', ['auth']);
        $router->post('/admin/administrators/update', 'AdminController@update', ['auth']);
        $router->post('/admin/administrators/update-password', 'AdminController@updatePassword', ['auth']);
        
        // Rutas Gmail
        $router->get('/gmail/connect', 'GmailController@connect', ['auth']);
        $router->get('/gmail/callback', 'GmailController@callback');
        
        // Cargar más rutas desde archivo de configuración
        // require_once base_path('routes/web.php');
    }
}
