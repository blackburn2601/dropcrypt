import os

# Flask Secret Key
SECRET_KEY = os.environ.get("DROPCRYPT_SECRET", "dev-secret-key")

# Time until expiration (in minutes)
TTL_MINUTES = int(os.environ.get("DROPCRYPT_TTL", "60"))

# Debug mode
DEBUG = bool(int(os.environ.get("DROPCRYPT_DEBUG", "0")))
