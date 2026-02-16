from google_auth_oauthlib.flow import InstalledAppFlow
from google.oauth2.credentials import Credentials

SCOPES = ['https://www.googleapis.com/auth/gmail.modify']

CLIENT_CONFIG = {
    "web": {
        "client_id": "32967724133-cctibkr6ccnofkil4k81a0eadatr0gjb.apps.googleusercontent.com",
        "client_secret": "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",
        "auth_uri": "https://accounts.google.com/o/oauth2/auth",
        "token_uri": "https://oauth2.googleapis.com/token",
        "redirect_uris": ["urn:ietf:wg:oauth:2.0:oob"]
    }
}

flow = InstalledAppFlow.from_client_config(CLIENT_CONFIG, SCOPES)
creds = flow.run_console()  # Esto abrirá un link para autorizar
print("Refresh Token:", creds.refresh_token)
