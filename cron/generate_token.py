from google_auth_oauthlib.flow import InstalledAppFlow
from google.oauth2.credentials import Credentials

SCOPES = ['https://www.googleapis.com/auth/gmail.modify']

CLIENT_CONFIG = {
    "web": {
        "client_id": "<TU_CLIENT_ID_GAC-S>",
        "client_secret": "<TU_CLIENT_SECRET_GAC-S>",
        "auth_uri": "https://accounts.google.com/o/oauth2/auth",
        "token_uri": "https://oauth2.googleapis.com/token",
        "redirect_uris": ["urn:ietf:wg:oauth:2.0:oob"]
    }
}

flow = InstalledAppFlow.from_client_config(CLIENT_CONFIG, SCOPES)
creds = flow.run_console()  # Esto abrirá un link para autorizar
print("Refresh Token:", creds.refresh_token)
