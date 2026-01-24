-- ============================================
-- GAC - Setup Base de Datos Local
-- Script para crear la BD en desarrollo local
-- ============================================

-- Crear base de datos local (si no existe)
CREATE DATABASE IF NOT EXISTS gac_local CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;

-- Usar la base de datos
USE gac_local;
