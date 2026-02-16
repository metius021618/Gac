# Por qué hay solicitudes repetidas y límites de la Gmail API

## Por qué las solicitudes se repiten

El bucle (`sync_loop.py`) está pensado para **actualizar constantemente**:

- Cada **CRON_READER_LOOP_SECONDS** (por defecto **0,5 s**) se ejecuta un ciclo.
- En cada ciclo se lanzan en paralelo los lectores: **IMAP**, **Gmail** y **Outlook**.
- Eso implica que el lector Gmail se ejecutaba **cada 0,5 s** → unas **120 veces por minuto**.
- Cada ejecución hace al menos: **1** `messages.list` + **N** `messages.get` (p. ej. 20) = **21 llamadas** a la API por ciclo.
- Total: **120 × 21 ≈ 2.520 llamadas/minuto** solo para Gmail, lo que consume la cuota por usuario y provoca **429 (User-rate limit exceeded)**.

Por eso las solicitudes son “repetidas”: el diseño es consultar muy seguido para estar actualizado, pero la Gmail API tiene un **límite por usuario por minuto** y no admite tantas ejecuciones seguidas.

---

## Límites oficiales de la Gmail API (Google)

| Límite              | Valor                         |
|---------------------|-------------------------------|
| **Por usuario**     | **15.000 unidades** por usuario por minuto |
| **Por proyecto**     | 1.200.000 unidades por minuto |

### Consumo por operación (resumen)

| Operación        | Unidades |
|------------------|----------|
| `messages.list` | 5        |
| `messages.get`  | 5        |

Por ciclo típico (1 list + 20 get): **5 + 20×5 = 105 unidades**.  
Si se ejecuta 1 vez por minuto: **105 unidades/min** → muy por debajo del límite.  
Si se ejecutaba 120 veces por minuto: **120 × 105 = 12.600 unidades/min** → se acerca o supera el límite (y además hay límites en ventanas cortas que provocan 429).

Fuente: [Usage limits - Gmail API](https://developers.google.com/gmail/api/reference/quota)

---

## Qué hace el sistema para no pasarse del límite

1. **Intervalo mínimo para Gmail**  
   Aunque el bucle siga cada 0,5 s, el lector **Gmail** solo se ejecuta como mínimo cada **CRON_GMAIL_MIN_INTERVAL_SECONDS** (por defecto **60 s**).  
   - IMAP y Outlook siguen en **cada** ciclo (máxima frecuencia).  
   - Gmail se espacia a **1 vez por minuto** (o el valor que pongas en `.env`).

2. **Variable de entorno**  
   En `.env` puedes poner, por ejemplo:
   ```env
   CRON_GMAIL_MIN_INTERVAL_SECONDS=60
   ```
   (60 = cada 60 s; puedes subir a 90 o 120 si aún ves 429.)

3. **Cooldown 429**  
   Si aun así Google devuelve **429**, se guarda el “Retry after” y **no** se vuelve a llamar a la API de Gmail hasta esa hora; el bucle y el resto de lectores (IMAP, Outlook) siguen corriendo con normalidad.

---

## Resumen

- **Por qué hay solicitudes repetidas:** porque el cron está diseñado para consultar muy seguido (cada 0,5 s) y en cada ciclo se ejecutaba también Gmail.
- **Límite que te afecta:** **15.000 unidades por usuario por minuto** (Gmail API).
- **Solución aplicada:** Gmail solo se ejecuta cada **60 s** (configurable); el resto del bucle y de lectores no se detiene y sigue con la misma intensidad.
