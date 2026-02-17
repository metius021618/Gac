# Estructura para el webhook Gmail (Pub/Sub push)

## Cómo debe estar organizado el proyecto en el servidor

La URL del webhook es `https://app.pocoyoni.com/gmail/push`. El servidor debe resolver esa ruta a un `index.php` que esté en una de estas ubicaciones (según cuál sea la **raíz web** del dominio):

- **Si la raíz web es `public`:**  
  `public/gmail/push/index.php`  
  → entonces `__DIR__` = `.../public/gmail/push` y la **raíz del proyecto** debe ser `dirname(__DIR__, 3)` = carpeta que contiene `public/`, `cron/`, `logs/`.

- **Si la raíz web es `public_html`:**  
  `public_html/gmail/push/index.php`  
  → entonces la **raíz del proyecto** debe ser `dirname(__DIR__, 3)` = carpeta que contiene `public_html/`, `cron/`, `logs/`.

En ambos casos, la **raíz del proyecto** (una carpeta arriba de `public` o `public_html`) debe contener:

- `cron/` (con `process_gmail_history.py`, etc.)
- `logs/` (donde se escribe `gmail_webhook.log` y `gmail_push_worker.log`)

Ejemplo de estructura correcta:

```
app.pocoyoni.com/          ← raíz del proyecto (basePath)
├── cron/
│   ├── process_gmail_history.py
│   └── ...
├── logs/
│   ├── gmail_webhook.log
│   └── gmail_push_worker.log
├── public/                ← o public_html (según Document Root)
│   └── gmail/
│       └── push/
│           ├── index.php
│           └── debug.php
└── public_html/
    └── gmail/
        └── push/
            ├── index.php
            └── debug.php
```

## Página de diagnóstico

Abre en el navegador:

**https://app.pocoyoni.com/gmail/push/debug.php**

Ahí verás:

- Rutas reales que ve el PHP (`__DIR__`, `basePath`, `logs/`, archivo de log).
- Si la carpeta `logs/` existe y es escribible.
- Si el archivo de log existe y es escribible.
- Si existe `cron/process_gmail_history.py`.
- Si una escritura de prueba en el log funciona.
- Las últimas líneas de `gmail_webhook.log`.
- Un botón para **simular un push** (POST al webhook) y comprobar si después aparece una línea en el log.

Usa esta página para comprobar que la arquitectura y permisos son correctos en tu servidor. Cuando todo funcione, puedes borrar o restringir el acceso a `debug.php` en producción.
