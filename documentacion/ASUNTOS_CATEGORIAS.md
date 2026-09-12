# Asuntos de correo: Generales, Código Temporal, Actualizar Hogar y Especiales

En **Admin → Asuntos de correo** hay cuatro pestañas:

| Pestaña | `category` | Quién lo usa |
|---------|------------|--------------|
| Generales | `general` | Consulta normal de códigos por plataforma |
| Código Temporal | `modo_hogar` | Tab “Código temporal” en `/hogar` |
| Actualizar Hogar | `modo_viaje` | Tab “Actualizar hogar” en `/hogar` |
| Asuntos especiales | `especial_leer` / `especial_no_leer` | Mismo asunto + cuerpo distinto |

Dentro de **Asuntos especiales** hay dos subsecciones:

| Subsección | `category` | `special_action` | Comportamiento del cron |
|------------|------------|------------------|-------------------------|
| Sí se leen | `especial_leer` | `leer` | Guarda en `codes` con `is_special=1` |
| No se leen | `especial_no_leer` | `no_leer` | No guarda; el historial Gmail avanza (no se relee) |

Cada regla especial exige **asunto exacto** + **fragmento de cuerpo** (`body_match`, normalizado HTML→texto).

## Consulta (opción A)

1. Se toma el último correo de la plataforma para el destinatario.
2. Si es especial (`is_special=1`) y el usuario **no** tiene `user_access.can_view_special=1`, se muestra el último **no especial**.
3. La clave maestra siempre puede ver especiales.
4. Por defecto nadie tiene el check (panel bajo Asuntos especiales).

## Migración / seed / prueba

```bash
python3 scripts/migrate_asuntos_especiales.py
python3 scripts/seed_asuntos_especiales_netflix.py
python3 scripts/test_asuntos_especiales_cron_sim.py
python -m unittest cron.tests.test_asuntos_especiales_classify -v
```

La UI pública de hogar/viaje no cambia: solo **`/hogar`**.

## Validación end-to-end (sin enviar Gmail)

Enviar un correo desde tu Gmail personal **no sirve** para esta prueba: el cron no detecta plataforma por el remitente, sino por el **asunto exacto** registrado (Netflix, Disney, etc.). Un mail tuyo no lleva esos asuntos.

En el servidor:

```bash
python3 scripts/validate_asuntos_especiales_e2e.py
```

Simula compra (descarta), OTP normal (guarda) y cambio de cuenta (guarda especial), y valida el gating de consulta. Limpia las filas de prueba al terminar.