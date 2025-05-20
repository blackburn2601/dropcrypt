import base64
import secrets
import hashlib
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

# 🔐 One-Time Token als Hash (für Datenbankvergleich)
def hash_token(token: str) -> str:
    return hashlib.sha256(token.encode()).hexdigest()

# 🔒 Daten verschlüsseln mit AES-GCM (sicher & authentifiziert)
def encrypt_data(plaintext: str, token: str) -> str:
    key = hashlib.sha256(token.encode()).digest()  # 32 Byte Key
    aesgcm = AESGCM(key)

    nonce = secrets.token_bytes(12)  # 96-bit Nonce
    ciphertext = aesgcm.encrypt(nonce, plaintext.encode('utf-8'), None)

    result = base64.b64encode(nonce + ciphertext).decode('utf-8')
    return result

# 🔓 Daten entschlüsseln mit Token
def decrypt_data(encrypted_b64: str, token: str) -> str:
    key = hashlib.sha256(token.encode()).digest()
    aesgcm = AESGCM(key)

    data = base64.b64decode(encrypted_b64)
    nonce = data[:12]
    ciphertext = data[12:]

    plaintext = aesgcm.decrypt(nonce, ciphertext, None).decode('utf-8')
    return plaintext
