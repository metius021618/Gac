<?php
/**
 * Vista previa del correo - Para ver cómo se renderiza el email
 * 
 * Uso: http://localhost:8001/view_email_preview.php?id=47
 */

// Cargar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar configuración
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Helpers/functions.php';

use Gac\Core\Database;

// Obtener ID del correo
$codeId = $_GET['id'] ?? null;

if (!$codeId) {
    die('Error: Debes proporcionar un ID. Ejemplo: view_email_preview.php?id=47');
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.code,
            c.email_from,
            c.subject,
            c.received_at,
            c.email_body,
            c.recipient_email,
            p.display_name as platform_name
        FROM codes c
        INNER JOIN platforms p ON c.platform_id = p.id
        WHERE c.id = :id
    ");
    
    $stmt->execute(['id' => $codeId]);
    $code = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$code) {
        die('Error: No se encontró el correo con ID ' . $codeId);
    }
    
    if (empty($code['email_body'])) {
        die('Error: Este correo no tiene contenido (email_body está vacío)');
    }
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa del Correo - <?= htmlspecialchars($code['subject']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #2a2a2a;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        
        .header {
            border-bottom: 2px solid #3a3a3a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .email-info {
            background: rgba(255, 255, 255, 0.05);
            border-left: 4px solid #0066ff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            align-items: flex-start;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #999;
            min-width: 120px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            color: #e0e0e0;
            flex: 1;
            word-break: break-word;
        }
        
        .email-body-container {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .email-body {
            color: #e0e0e0;
            line-height: 1.6;
        }
        
        .email-body img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .email-body a {
            color: #0066ff;
            text-decoration: underline;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #0066ff;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(0, 102, 255, 0.1);
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .back-link:hover {
            background: rgba(0, 102, 255, 0.2);
        }
        
        .warning {
            background: rgba(255, 193, 7, 0.15);
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Vista Previa del Correo</h1>
            <p style="color: #999;">Así se vería el correo en el modal de la aplicación</p>
        </div>
        
        <div class="email-info">
            <div class="info-row">
                <span class="info-label">De:</span>
                <span class="info-value"><?= htmlspecialchars($code['email_from']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Asunto:</span>
                <span class="info-value"><?= htmlspecialchars($code['subject']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span class="info-value"><?= date('d/m/Y H:i', strtotime($code['received_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Plataforma:</span>
                <span class="info-value"><?= htmlspecialchars($code['platform_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Usuario:</span>
                <span class="info-value"><?= htmlspecialchars($code['recipient_email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Código:</span>
                <span class="info-value" style="font-family: 'Courier New', monospace; font-size: 18px; color: #0066ff; font-weight: bold;">
                    <?= htmlspecialchars($code['code']) ?>
                </span>
            </div>
        </div>
        
        <div class="email-body-container">
            <div class="email-body">
                <?php
                // Detectar si es HTML
                $isHTML = strpos(trim($code['email_body']), '<') === 0;
                
                if ($isHTML) {
                    // Mostrar HTML directamente
                    echo $code['email_body'];
                } else {
                    // Convertir saltos de línea a <br>
                    echo nl2br(htmlspecialchars($code['email_body']));
                }
                ?>
            </div>
        </div>
        
        <a href="javascript:history.back()" class="back-link">← Volver</a>
    </div>
</body>
</html>
