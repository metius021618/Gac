#!/usr/bin/env python3
"""
GAC - Receptor del webhook de Gmail (Pub/Sub push)
Recibe el POST de Google Cloud Pub/Sub cuando Gmail detecta cambios en la bandeja.
Responsabilidad única: validar payload, extraer historyId y disparar el worker de procesamiento.
NO parsea correos ni escribe en BD.

Uso como servidor HTTP (para configurar como URL de push en la suscripción Pub/Sub):
  python cron/gmail_webhook_receiver.py
  o con Flask: flask --app cron.gmail_webhook_receiver run --host 0.0.0.0 --port 5050

Variable de entorno opcional: GMAIL_WEBHOOK_PORT (default 5050).
"""

import os
import sys
import base64
import json
import logging
import subprocess

script_dir = os.path.dirname(os.path.abspath(__file__))
parent_dir = os.path.dirname(script_dir)
sys.path.insert(0, parent_dir)
os.chdir(parent_dir)

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


def trigger_worker(new_history_id: str) -> bool:
    """Dispara el worker de procesamiento (history.list + metadata + full si match)."""
    worker_script = os.path.join(script_dir, 'process_gmail_history.py')
    if not os.path.isfile(worker_script):
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
    app = create_app()
    if app is not None:
        port = int(os.getenv('GMAIL_WEBHOOK_PORT', '5050'))
        app.run(host='0.0.0.0', port=port)
    else:
        run_standalone_server()
