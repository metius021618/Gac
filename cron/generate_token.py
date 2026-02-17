"""
Generar refresh token para Gmail (OAuth).
run_console() ya no existe en google-auth-oauthlib; se usa run_local_server().
Debes ejecutar esto en un equipo donde se pueda abrir el navegador (ej. tu PC).
Usa GMAIL_CLIENT_ID y GMAIL_CLIENT_SECRET del .env del proyecto (mismo cliente que la app).
"""
import os
from pathlib import Path

from dotenv import load_dotenv
from google_auth_oauthlib.flow import InstalledAppFlow

# Cargar .env del proyecto (raíz del repo)
_env_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(_env_path)

SCOPES = ['https://www.googleapis.com/auth/gmail.modify']

# Este script abre un servidor local; la redirección es a localhost (no a app.pocoyoni.com).
# En Google Cloud Console añade en "URIs de redirección autorizados": http://localhost:8090/
PORT = 8090

_client_id = os.getenv("GMAIL_CLIENT_ID", "").strip()
_client_secret = os.getenv("GMAIL_CLIENT_SECRET", "").strip()
if not _client_id or not _client_secret:
    raise SystemExit(
        "Falta GMAIL_CLIENT_ID o GMAIL_CLIENT_SECRET en .env. "
        "Usa los mismos valores que en el servidor (paso 3 de la guía)."
    )

CLIENT_CONFIG = {
    "web": {
        "client_id": _client_id,
        "client_secret": _client_secret,
        "auth_uri": "https://accounts.google.com/o/oauth2/auth",
        "token_uri": "https://oauth2.googleapis.com/token",
        "redirect_uris": [f"http://localhost:{PORT}/"]
    }
}

flow = InstalledAppFlow.from_client_config(CLIENT_CONFIG, SCOPES)
creds = flow.run_local_server(port=PORT)  # Abre el navegador para autorizar
print("Refresh Token:", creds.refresh_token)
