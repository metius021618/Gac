# Asuntos de correo: Generales, Modo Hogar y Modo Viaje

En **Admin → Asuntos de correo** hay tres pestañas:

| Pestaña | `category` | Quién lo usa |
|---------|------------|--------------|
| Generales | `general` | Consulta normal de códigos por plataforma |
| Modo Hogar | `modo_hogar` | Vista pública `/hogar` |
| Modo Viaje | `modo_viaje` | Vista pública `/MViaje` |

El **cron** lee todos los asuntos activos (las tres categorías). Si agregas o cambias un asunto en Modo Hogar o Modo Viaje, el lector lo toma en el siguiente ciclo.

La coincidencia es **exacta** (el asunto del correo debe ser igual al registrado).

El asunto histórico `Tu código de acceso temporal de Netflix` se mueve a Modo Hogar con:

```bash
php scripts/migrate_email_subjects_modo_hogar.php
```
