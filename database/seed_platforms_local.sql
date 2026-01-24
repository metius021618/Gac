-- ============================================
-- GAC - Seed de Plataformas (LOCAL)
-- ============================================
-- Este script inserta las plataformas iniciales

USE gac_local;

-- Insertar plataformas
INSERT IGNORE INTO platforms (name, display_name, enabled, config) VALUES
('netflix', 'Netflix', 1, '{"color": "#E50914", "icon": "netflix"}'),
('disney', 'Disney+', 1, '{"color": "#113CCF", "icon": "disney"}'),
('prime', 'Amazon Prime Video', 1, '{"color": "#00A8E1", "icon": "prime"}'),
('spotify', 'Spotify', 1, '{"color": "#1DB954", "icon": "spotify"}'),
('crunchyroll', 'Crunchyroll', 1, '{"color": "#F47521", "icon": "crunchyroll"}'),
('paramount', 'Paramount+', 1, '{"color": "#0072FF", "icon": "paramount"}'),
('chatgpt', 'ChatGPT', 1, '{"color": "#10A37F", "icon": "chatgpt"}'),
('canva', 'Canva', 0, '{"color": "#00C4CC", "icon": "canva"}');
