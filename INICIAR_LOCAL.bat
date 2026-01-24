@echo off
echo ========================================
echo   GAC - Servidor Local (Puerto 8001)
echo ========================================
echo.

REM Verificar si existe .env
if not exist .env (
    echo [ADVERTENCIA] Archivo .env no encontrado!
    echo.
    echo El servidor iniciará pero puede haber errores de configuración.
    echo.
)

REM Verificar si existe vendor/autoload.php
if not exist vendor\autoload.php (
    echo [INFO] Instalando dependencias de Composer...
    composer install --ignore-platform-req=ext-imap
    if errorlevel 1 (
        echo [ERROR] Error al instalar dependencias
        pause
        exit /b 1
    )
)

echo [INFO] Iniciando servidor en http://localhost:8001
echo [INFO] Presiona Ctrl+C para detener el servidor
echo.
echo IMPORTANTE: Asegúrate de estar en el directorio gac\SISTEMA_GAC
echo.

REM Iniciar servidor con router.php
php -S localhost:8001 -t public public/router.php
