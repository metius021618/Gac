# Modo Viaje — Diseño

## Objetivo
Consulta pública de correos “Estoy de viaje” (u otros asuntos configurables), paralela a `/hogar` (código temporal Netflix), con mantenedor de asuntos exactos y lectura por el cron.

## Decisiones
- **Consulta:** último correo cuyo `subject` coincida **exactamente** con cualquiera de los asuntos `category = modo_viaje` (opción A).
- **Ruta pública:** `GET/POST /MViaje`.
- **Entrada desde Hogar:** botón “Modo Viaje” → `/MViaje` (y enlace de vuelta a `/hogar`).
- **Asuntos:** columna `email_subjects.category` (`general` | `modo_hogar` | `modo_viaje`). Pestañas **Generales / Modo Hogar / Modo Viaje** en `/admin/email-subjects`.
- **CRUD:** mismo flujo del front (`POST /admin/email-subjects` JSON con `platform_id`, `subject_line`, `category`).
- **Cron:** sigue leyendo todos los asuntos activos; los de Modo Viaje se registran igual (coincidencia exacta).
- **Respuesta UI:** correo completo en modal (como Hogar).

## Fuera de alcance
- Cambio de catch-all / IMAP.
- Extracción de dígitos del cuerpo (se muestra el email completo).
