import os

# Flask Secret Key (für Sessions, CSRF usw.)
SECRET_KEY = os.environ.get("DROPCRYPT_SECRET", "dev-secret-key")

# SQLite Datenbankpfad
DB_PATH = os.environ.get("DROPCRYPT_DB_PATH", "dropcrypt.db")

# Zeit bis Ablauf (in Minuten)
TTL_MINUTES = int(os.environ.get("DROPCRYPT_TTL", "60"))

# Debug-Modus
DEBUG = bool(int(os.environ.get("DROPCRYPT_DEBUG", "0")))
