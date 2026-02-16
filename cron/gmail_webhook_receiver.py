#!/usr/bin/env python3
"""
GAC - Receptor del webhook de Gmail (Pub/Sub push)
Recibe el POST de Google Cloud Pub/Sub cuando Gmail detecta cambios en la bandeja.
Responsabilidad única: validar payload, extraer historyId y disparar el worker de procesamiento.
NO parsea correos ni escribe en BD.

URL del webhook (configurar en la suscripción Push de Pub/Sub): https://app.pocoyoni.com/gmail/push

Uso como servidor HTTP:
  python cron/gmail_webhook_receiver.py
  o con Flask: flask --app cron.gmail_webhook_receiver run --host 0.0.0.0 --port 5050

Variables de entorno opcionales: GMAIL_WEBHOOK_PORT (5050), GMAIL_WEBHOOK_URL (URL pública).
"""

import os
import sys
import base64
import json
import logging
import subprocess
import time

script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)
os.chdir(parent_dir)

# Lock para event bursts: solo un worker a la vez (solución futura: cola Redis/DB)
LOCK_DIR = os.path.join(parent_dir, 'logs')
LOCK_FILE = os.path.join(LOCK_DIR, 'gmail_worker.lock')
LOCK_MAX_AGE_SEC = 120  # si el lock tiene más de esto, considerarlo stale

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


def decode_pubsub_data(data_b64: str) -> dict:
    """Decodificar message.data (base64url) a JSON con emailAddress e historyId."""
    if not data_b64:
        return {}
    try:
        # Base64url: sustituir -_ por +/
        padding = 4 - len(data_b64) % 4
        if padding != 4:
            data_b64 += '=' * padding
        raw = base64.urlsafe_b64decode(data_b64)
        return json.loads(raw.decode('utf-8'))
    except Exception as e:
        logger.warning("Decode pubsub data failed: %s", e)
        return {}


def handle_notification(body: dict):
    """
    Valida el body del POST (Pub/Sub push) y extrae historyId.
    Returns: (ok: bool, new_history_id: str or '')
    """
    try:
        message = body.get('message') or {}
        data_b64 = message.get('data')
        if not data_b64:
            return False, ''
        payload = decode_pubsub_data(data_b64)
        history_id = payload.get('historyId') or payload.get('history_id')
        if not history_id:
            return False, ''
        return True, str(history_id).strip()
    except Exception as e:
        logger.warning("handle_notification error: %s", e)
        return False, ''


def _worker_lock_acquire():
    """Intentar adquirir lock (un solo worker a la vez). Returns True si lock adquirido."""
    if not os.path.isdir(LOCK_DIR):
        try:
            os.makedirs(LOCK_DIR, exist_ok=True)
        except OSError:
            return False
    if os.path.isfile(LOCK_FILE):
        try:
            age = time.time() - os.path.getmtime(LOCK_FILE)
            if age < LOCK_MAX_AGE_SEC:
                return False  # otro worker en curso
        except OSError:
            pass
        try:
            os.remove(LOCK_FILE)
        except OSError:
            pass
    try:
        with open(LOCK_FILE, 'w') as f:
            f.write(str(time.time()))
        return True
    except OSError:
        return False


def _worker_lock_release():
    try:
        if os.path.isfile(LOCK_FILE):
            os.remove(LOCK_FILE)
    except OSError:
        pass


def trigger_worker(new_history_id: str) -> bool:
    """
    Dispara el worker de procesamiento (history.list + metadata + full si match).
    Solo un worker a la vez (lock file) para evitar bursts de eventos; si ya hay uno en curso, se omite
    y el próximo push traerá el historyId más reciente. Solución futura: cola interna (Redis o DB).
    """
    if not _worker_lock_acquire():
        logger.info("Worker ya en ejecución (event burst), omitiendo este push. Próximo push procesará el historyId más reciente.")
        return True  # 200 para no reintentar
    worker_script = os.path.join(script_dir, 'process_gmail_history.py')
    if not os.path.isfile(worker_script):
        _worker_lock_release()
        logger.error("Worker no encontrado: %s", worker_script)
        return False
    try:
        result = subprocess.run(
            [sys.executable, worker_script, '--history-id', new_history_id],
            cwd=parent_dir,
            capture_output=True,
            timeout=120,
            text=True
        )
        if result.returncode != 0 and result.stderr:
            logger.warning("Worker stderr: %s", result.stderr[:500])
        return result.returncode == 0
    except subprocess.TimeoutExpired:
        logger.warning("Worker timeout 120s")
        return False
    except Exception as e:
        logger.exception("Trigger worker failed: %s", e)
        return False
    finally:
        _worker_lock_release()


# --- Flask app (recomendado para producción como URL push) ---
def create_app():
    try:
        from flask import Flask, request
    except ImportError:
        logger.warning("Flask no instalado. Instale: pip install flask")
        return None

    app = Flask(__name__)

    @app.route('/gmail/push', methods=['POST'])
    def gmail_push():
        """Endpoint que recibe el push de Pub/Sub. Responde 200 para acusar recibo."""
        try:
            body = request.get_json(force=True, silent=True) or {}
        except Exception:
            body = {}
        ok, new_history_id = handle_notification(body)
        if not ok:
            logger.info("Push recibido pero sin historyId válido (ignorado).")
            return '', 200  # 200 para que Pub/Sub no reintente
        logger.info("Push Gmail: new historyId=%s", new_history_id)
        trigger_worker(new_history_id)
        return '', 200

    @app.route('/health', methods=['GET'])
    def health():
        return 'ok', 200

    return app


def run_standalone_server():
    """Servidor HTTP mínimo sin Flask (solo stdlib) para recibir POST."""
    from http.server import HTTPServer, BaseHTTPRequestHandler

    class Handler(BaseHTTPRequestHandler):
        def do_POST(self):
            if self.path.rstrip('/') != '/gmail/push':
                self.send_response(404)
                self.end_headers()
                return
            length = int(self.headers.get('Content-Length', 0))
            raw = self.rfile.read(length) if length else b''
            try:
                body = json.loads(raw.decode('utf-8'))
            except Exception:
                body = {}
            ok, new_history_id = handle_notification(body)
            if ok and new_history_id:
                logger.info("Push Gmail: new historyId=%s", new_history_id)
                trigger_worker(new_history_id)
            self.send_response(200)
            self.end_headers()

        def log_message(self, format, *args):
            logger.info("%s - %s", self.address_string(), format % args)

    port = int(os.getenv('GMAIL_WEBHOOK_PORT', '5050'))
    server = HTTPServer(('0.0.0.0', port), Handler)
    logger.info("Webhook Gmail escuchando en http://0.0.0.0:%s/gmail/push", port)
    server.serve_forever()


if __name__ == '__main__':
    try:
        from cron.config import GMAIL_CONFIG
        webhook_url = GMAIL_CONFIG.get('webhook_url', 'https://app.pocoyoni.com/gmail/push')
    except Exception:
        webhook_url = 'https://app.pocoyoni.com/gmail/push'
    logger.info("Webhook URL (configurar en Pub/Sub): %s", webhook_url)
    app = create_app()
    if app is not None:
        port = int(os.getenv('GMAIL_WEBHOOK_PORT', '5050'))
        app.run(host='0.0.0.0', port=port)
    else:
        run_standalone_server()
