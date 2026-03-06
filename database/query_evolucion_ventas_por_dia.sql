-- =============================================================================
-- DIAGNÓSTICO: Evolución de ventas y cuentas registradas por día
-- =============================================================================
-- El gráfico "Evolución Mensual de Ventas" se calcula así:
--   1. Se toma la tabla user_access (cada fila = una cuenta asignada/vendida).
--   2. Se filtra por created_at dentro del rango del filtro (ej. últimos 30 días).
--   3. Se agrupa por AÑO y MES: GROUP BY YEAR(created_at), MONTH(created_at).
--   4. La etiqueta del eje X es DATE_FORMAT(created_at, '%b ''%y') → ej. "Feb '26" = Febrero 2026.
--
-- Si ves "Mar '26" es porque hay registros con created_at en marzo de 2026.
-- Eso puede deberse a: reloj del servidor en 2026, o datos migrados con año 2026.
-- =============================================================================

-- 1) Fecha actual del servidor y rango de fechas en la tabla
SELECT 
    CURDATE() AS fecha_actual_servidor,
    MIN(DATE(ua.created_at)) AS primera_cuenta_registrada,
    MAX(DATE(ua.created_at)) AS ultima_cuenta_registrada,
    COUNT(*) AS total_cuentas_user_access
FROM user_access ua;

-- 2) Cuentas registradas POR DÍA (últimos 90 días desde hoy)
--    Así ves exactamente qué días tienen datos y cuántos registros.
SELECT 
    DATE(ua.created_at) AS dia,
    COUNT(*) AS cuentas_registradas
FROM user_access ua
WHERE ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
GROUP BY DATE(ua.created_at)
ORDER BY dia DESC;

-- 3) Lo mismo que usa el gráfico: agrupado por MES (para comparar)
SELECT 
    DATE_FORMAT(ua.created_at, '%b ''%y') AS mes_etiqueta,
    YEAR(ua.created_at) AS anio,
    MONTH(ua.created_at) AS mes,
    COUNT(*) AS total
FROM user_access ua
WHERE ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  AND ua.created_at <= CURDATE()
GROUP BY YEAR(ua.created_at), MONTH(ua.created_at)
ORDER BY anio, mes;

-- 4) Si quieres ver solo últimos 30 días (como el filtro por defecto) por día
SELECT 
    DATE(ua.created_at) AS dia,
    COUNT(*) AS cuentas_registradas
FROM user_access ua
WHERE ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  AND ua.created_at <= CURDATE()
GROUP BY DATE(ua.created_at)
ORDER BY dia ASC;
