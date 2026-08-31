# Asuntos de correo: Generales, Código Temporal y Actualizar Hogar

En **Admin → Asuntos de correo** hay tres pestañas (en este orden):

| Pestaña | `category` | Quién lo usa |
|---------|------------|--------------|
| Generales | `general` | Consulta normal de códigos por plataforma |
| Código Temporal | `modo_hogar` | Tab “Código temporal” en `/hogar` |
| Actualizar Hogar | `modo_viaje` | Tab “Actualizar hogar” en `/hogar` |

La UI pública es solo **`/hogar`** (cambiar de tab no cambia la URL). El POST a `/MViaje` sigue usándose internamente para la consulta de Actualizar Hogar.

El **cron** lee todos los asuntos activos (las tres categorías). Si agregas o cambias un asunto en Código Temporal o Actualizar Hogar, el lector lo toma en el siguiente ciclo.

La coincidencia es **exacta** (el asunto del correo debe ser igual al registrado).

El asunto histórico `Tu código de acceso temporal de Netflix` se mueve a Código Temporal (`modo_hogar`) con:

```bash
php scripts/migrate_email_subjects_modo_hogar.php
```
