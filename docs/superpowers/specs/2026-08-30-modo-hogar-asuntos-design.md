# Modo Hogar — Asuntos en tabla

## Objetivo
`/hogar` deja de usar el asunto hardcodeado y lee los asuntos activos de `email_subjects.category = modo_hogar`, igual que `/MViaje` con `modo_viaje`.

## Decisiones
- **Admin → Asuntos de correo:** 3 pestañas: **Generales**, **Modo Hogar**, **Modo Viaje**.
- **Consulta `/hogar`:** último correo cuyo `subject` coincida exactamente con cualquiera de los asuntos `modo_hogar`.
- **Consulta `/MViaje`:** sin cambio (`modo_viaje`).
- **Cron:** sigue leyendo **todos** los asuntos activos (cualquier categoría). Si se agrega o edita un asunto en Modo Hogar, el cron lo filtra y guarda.
- **Migración:** el asunto existente `Tu código de acceso temporal de Netflix` pasa de `general` a `modo_hogar` (`scripts/migrate_email_subjects_modo_hogar.php`).
