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
        $router->get('/politica-privacidad', 'LegalController@politicaPrivacidad');
        $router->get('/condiciones-servicio', 'LegalController@condicionesServicio');

        // Rutas API
        $router->post('/api/v1/codes/consult', 'CodeController@apiConsult');
        $router->post('/api/v1/codes/consult-with-sync', 'CodeController@apiConsultWithSync');
        $router->get('/api/v1/sync-emails', 'CodeController@triggerEmailSync');
        $router->post('/api/v1/codes/consult-by-domain', 'ApiController@consultByDomain');
        $router->get('/api/v1/codes/list-by-domain', 'ApiController@listByDomain');
        
        // Rutas de gestión de cuentas de email (requieren autenticación)
        $router->get('/admin/email-accounts', 'EmailAccountController@index', ['auth']);
        $router->get('/admin/email-accounts/create', 'EmailAccountController@create', ['auth']);
        $router->post('/admin/email-accounts', 'EmailAccountController@store', ['auth']);
        $router->get('/admin/email-accounts/edit', 'EmailAccountController@edit', ['auth']);
        $router->post('/admin/email-accounts/update', 'EmailAccountController@update', ['auth']);
        $router->post('/admin/email-accounts/delete', 'EmailAccountController@destroy', ['auth']);
        $router->post('/admin/email-accounts/delete-account', 'EmailAccountController@destroyByAccountId', ['auth']);
        $router->post('/admin/email-accounts/bulk-delete', 'EmailAccountController@bulkDeleteDispatch', ['auth']);
        $router->post('/admin/email-accounts/bulk-delete-ids', 'EmailAccountController@bulkDelete', ['auth']);
        $router->post('/admin/email-accounts/toggle-status', 'EmailAccountController@toggleStatus', ['auth']);
        $router->get('/admin/email-accounts/bulk-register', 'EmailAccountController@bulkRegister', ['auth']);
        $router->post('/admin/email-accounts/bulk-register', 'EmailAccountController@bulkRegisterStore', ['auth']);
        $router->post('/admin/email-accounts/add-stock', 'EmailAccountController@addStock', ['auth']);
        
        // Rutas de autenticación
        $router->get('/login', 'AuthController@showLogin');
        $router->post('/login', 'AuthController@login');
        $router->get('/logout', 'AuthController@logout');
        $router->post('/logout', 'AuthController@logout');
        
        // Rutas administrativas (requieren autenticación)
        $router->get('/admin/dashboard', 'DashboardController@index', ['auth']);
        $router->get('/admin/users', 'UserController@index', ['auth']);
        $router->get('/admin/settings', 'SettingsController@index', ['auth']);
        $router->post('/admin/settings/update', 'SettingsController@update', ['auth']);
        $router->get('/admin/reader-loop/status', 'CodeController@readerLoopStatus', ['auth']);
        $router->post('/admin/reader-loop/start', 'CodeController@startReaderLoop', ['auth']);
        
        // Rutas de plataformas
        $router->get('/admin/platforms', 'PlatformController@index', ['auth']);
        $router->post('/admin/platforms/toggle-status', 'PlatformController@toggleStatus', ['auth']);
        $router->post('/admin/platforms/store', 'PlatformController@store', ['auth']);
        $router->post('/admin/platforms/delete', 'PlatformController@destroy', ['auth']);
        
        // Rutas de asuntos de email
        $router->get('/admin/email-subjects', 'EmailSubjectController@index', ['auth']);
        $router->get('/admin/email-subjects/create', 'EmailSubjectController@create', ['auth']);
        $router->post('/admin/email-subjects', 'EmailSubjectController@store', ['auth']);
        $router->get('/admin/email-subjects/edit', 'EmailSubjectController@edit', ['auth']);
        $router->post('/admin/email-subjects/update', 'EmailSubjectController@update', ['auth']);
        $router->post('/admin/email-subjects/delete', 'EmailSubjectController@destroy', ['auth']);
        
        // Rutas de administradores
        $router->get('/admin/administrators', 'AdminController@index', ['auth']);
        $router->get('/admin/administrators/edit', 'AdminController@edit', ['auth']);
        $router->post('/admin/administrators/update', 'AdminController@update', ['auth']);
        $router->post('/admin/administrators/update-password', 'AdminController@updatePassword', ['auth']);
        $router->post('/admin/administrators/store', 'AdminController@store', ['auth']);
        
        // Rutas de Registro de Accesos
        $router->get('/admin/user-access', 'UserAccessController@index', ['auth']);
        $router->get('/admin/user-access/list', 'UserAccessController@list', ['auth']);
        $router->post('/admin/user-access', 'UserAccessController@store', ['auth']);
        $router->post('/admin/user-access/delete', 'UserAccessController@delete', ['auth']);
        
        // Rutas Gmail
        $router->get('/gmail/connect', 'GmailController@connect', ['auth']);
        $router->get('/gmail/callback', 'GmailController@callback');
        
        // Rutas Outlook
        $router->get('/outlook/connect', 'OutlookController@connect', ['auth']);
        $router->get('/outlook/callback', 'OutlookController@callback');
        
        // Cargar más rutas desde archivo de configuración
        // require_once base_path('routes/web.php');
    }
}
