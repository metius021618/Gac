<?php
/**
 * GAC - Request Handler
 * 
 * @package Gac\Core
 */

namespace Gac\Core;

class Request
{
    /**
     * Obtener método HTTP
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
    
    /**
     * Obtener path de la URL
     */
    public function path(): string
    {
        // Obtener REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Extraer solo el path (sin query string)
        $path = parse_url($requestUri, PHP_URL_PATH);
        
        // Si parse_url falla, usar el path original
        if ($path === null || $path === false) {
            $path = $requestUri;
        }
        
        // Si el Document Root está en /public, el SCRIPT_NAME podría ser /index.php
        // En ese caso, no necesitamos remover nada porque Apache ya lo maneja
        // Solo necesitamos normalizar el path
        
        // Normalizar: remover trailing slash excepto para root
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }
        
        return $path;
    }
    
    /**
     * Obtener parámetros GET
     */
    public function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Obtener parámetros POST
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Obtener todos los inputs
     */
    public function all(): array
    {
        $data = array_merge($_GET, $_POST);
        
        // Si es JSON, parsear el body
        if ($this->isJson()) {
            $json = json_decode(file_get_contents('php://input'), true);
            if ($json) {
                $data = array_merge($data, $json);
            }
        }
        
        return $data;
    }
    
    /**
     * Obtener input específico (GET, POST o JSON)
     */
    public function input(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->all();
        }
        
        $all = $this->all();
        return $all[$key] ?? $default;
    }
    
    /**
     * Verificar si el request es JSON
     */
    private function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }
    
    /**
     * Obtener header
     */
    public function header(string $key): ?string
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($key));
        return $_SERVER[$key] ?? null;
    }
    
    /**
     * Verificar si es AJAX
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
