"""
Generar refresh token para Gmail (OAuth).
run_console() ya no existe en google-auth-oauthlib; se usa run_local_server().
Debes ejecutar esto en un equipo donde se pueda abrir el navegador (ej. tu PC).
"""
from google_auth_oauthlib.flow import InstalledAppFlow

SCOPES = ['https://www.googleapis.com/auth/gmail.modify']

# En Google Cloud Console → Credenciales → tu cliente OAuth 2.0
# añade en "URIs de redirección autorizados": http://localhost:8090/
# (si 8090 está en uso, cambia PORT abajo y añade http://localhost:PORT/ en Google)
PORT = 8090
CLIENT_CONFIG = {
    "web": {
        "client_id": "32967724133-cctibkr6ccnofkil4k81a0eadatr0gjb.apps.googleusercontent.com",
        "client_secret": "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",
        "auth_uri": "https://accounts.google.com/o/oauth2/auth",
        "token_uri": "https://oauth2.googleapis.com/token",
        "redirect_uris": [f"http://localhost:{PORT}/"]
    }
}

flow = InstalledAppFlow.from_client_config(CLIENT_CONFIG, SCOPES)
creds = flow.run_local_server(port=PORT)  # Abre el navegador para autorizar
print("Refresh Token:", creds.refresh_token)
