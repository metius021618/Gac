<?php
/**
 * GAC - Repositorio de Configuración
 * Maneja las configuraciones del sistema
 * 
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class SettingsRepository
{
    /**
     * Obtener todas las configuraciones
     */
    public function findAll(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM settings ORDER BY category, `key`");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener configuraciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener configuración por clave
     */
    public function findByKey(string $key): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM settings WHERE `key` = :key LIMIT 1");
            $stmt->execute([':key' => $key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener configuración por clave: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener valor de configuración por clave
     */
    public function getValue(string $key, string $default = ''): string
    {
        try {
            $setting = $this->findByKey($key);
            return $setting ? $setting['value'] : $default;
        } catch (\Exception $e) {
            // Si hay error (tabla no existe, etc.), retornar valor por defecto
            error_log("Error al obtener valor de configuración '{$key}': " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Obtener configuraciones por categoría
     */
    public function findByCategory(string $category): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM settings WHERE category = :category ORDER BY `key`");
            $stmt->execute([':category' => $category]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener configuraciones por categoría: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar configuración
     */
    public function update(string $key, string $value, string $description = null): bool
    {
        try {
            $db = Database::getConnection();
            
            // Verificar si existe
            $existing = $this->findByKey($key);
            
            if ($existing) {
                // Actualizar
                $sql = "UPDATE settings SET `value` = :value, updated_at = NOW()";
                $params = [':key' => $key, ':value' => $value];
                
                if ($description !== null) {
                    $sql .= ", description = :description";
                    $params[':description'] = $description;
                }
                
                $sql .= " WHERE `key` = :key";
                
                $stmt = $db->prepare($sql);
                return $stmt->execute($params);
            } else {
                // Crear nueva configuración
                $stmt = $db->prepare("
                    INSERT INTO settings (`key`, `value`, description, category) 
                    VALUES (:key, :value, :description, 'general')
                ");
                return $stmt->execute([
                    ':key' => $key,
                    ':value' => $value,
                    ':description' => $description ?? ''
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error al actualizar configuración: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tiempo de sesión en segundos
     */
    public function getSessionTimeout(): int
    {
        try {
            $hours = (int) $this->getValue('session_timeout_hours', '1');
            // Validar que esté en el rango permitido (1-7 horas)
            if ($hours < 1 || $hours > 7) {
                $hours = 1;
            }
            return $hours * 3600; // Convertir horas a segundos
        } catch (\Exception $e) {
            // Si hay error (tabla no existe, etc.), usar valor por defecto
            error_log("Error al obtener timeout de sesión: " . $e->getMessage());
            return 3600; // 1 hora por defecto
        }
    }
}
