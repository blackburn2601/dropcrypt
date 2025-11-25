/**
 * DropCrypt Anonymous Messaging - Client-Side Encryption
 * Zero-knowledge end-to-end encryption using RSA + AES-256-GCM
 */

class CryptoHelper {
    constructor() {
        this.keyPair = null;
        this.privateKeyEncrypted = null;
    }

    /**
     * Generate RSA-4096 key pair
     */
    async generateKeyPair() {
        this.keyPair = await window.crypto.subtle.generateKey(
            {
                name: 'RSA-OAEP',
                modulusLength: 4096,
                publicExponent: new Uint8Array([1, 0, 1]),
                hash: 'SHA-256'
            },
            true,
            ['encrypt', 'decrypt']
        );

        return this.keyPair;
    }

    /**
     * Export public key to base64
     */
    async exportPublicKey(publicKey = null) {
        const key = publicKey || this.keyPair.publicKey;
        const exported = await window.crypto.subtle.exportKey('spki', key);
        return this.arrayBufferToBase64(exported);
    }

    /**
     * Export private key to base64
     */
    async exportPrivateKey(privateKey = null) {
        const key = privateKey || this.keyPair.privateKey;
        const exported = await window.crypto.subtle.exportKey('pkcs8', key);
        return this.arrayBufferToBase64(exported);
    }

    /**
     * Import public key from base64
     */
    async importPublicKey(base64Key) {
        const keyData = this.base64ToArrayBuffer(base64Key);
        
        return await window.crypto.subtle.importKey(
            'spki',
            keyData,
            {
                name: 'RSA-OAEP',
                hash: 'SHA-256'
            },
            true,
            ['encrypt']
        );
    }

    /**
     * Import private key from base64
     */
    async importPrivateKey(base64Key) {
        const keyData = this.base64ToArrayBuffer(base64Key);
        
        return await window.crypto.subtle.importKey(
            'pkcs8',
            keyData,
            {
                name: 'RSA-OAEP',
                hash: 'SHA-256'
            },
            true,
            ['decrypt']
        );
    }

    /**
     * Encrypt private key with password (AES-256-GCM)
     */
    async encryptPrivateKey(privateKeyBase64, password) {
        const salt = window.crypto.getRandomValues(new Uint8Array(16));
        const iv = window.crypto.getRandomValues(new Uint8Array(12));

        const keyMaterial = await window.crypto.subtle.importKey(
            'raw',
            new TextEncoder().encode(password),
            'PBKDF2',
            false,
            ['deriveBits', 'deriveKey']
        );

        const key = await window.crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: salt,
                iterations: 600000,
                hash: 'SHA-256'
            },
            keyMaterial,
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
        );

        const privateKeyData = new TextEncoder().encode(privateKeyBase64);
        const encrypted = await window.crypto.subtle.encrypt(
            {
                name: 'AES-GCM',
                iv: iv
            },
            key,
            privateKeyData
        );

        return {
            encrypted: this.arrayBufferToBase64(encrypted),
            salt: this.arrayBufferToBase64(salt),
            iv: this.arrayBufferToBase64(iv)
        };
    }

    /**
     * Decrypt private key with password
     */
    async decryptPrivateKey(encryptedData, password) {
        const salt = this.base64ToArrayBuffer(encryptedData.salt);
        const iv = this.base64ToArrayBuffer(encryptedData.iv);
        const encrypted = this.base64ToArrayBuffer(encryptedData.encrypted);

        const keyMaterial = await window.crypto.subtle.importKey(
            'raw',
            new TextEncoder().encode(password),
            'PBKDF2',
            false,
            ['deriveBits', 'deriveKey']
        );

        const key = await window.crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: salt,
                iterations: 600000,
                hash: 'SHA-256'
            },
            keyMaterial,
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
        );

        const decrypted = await window.crypto.subtle.decrypt(
            {
                name: 'AES-GCM',
                iv: iv
            },
            key,
            encrypted
        );

        return new TextDecoder().decode(decrypted);
    }

    /**
     * Encrypt message content with AES-256-GCM
     */
    async encryptMessage(message) {
        const aesKey = await window.crypto.subtle.generateKey(
            {
                name: 'AES-GCM',
                length: 256
            },
            true,
            ['encrypt', 'decrypt']
        );

        const iv = window.crypto.getRandomValues(new Uint8Array(12));
        const messageData = new TextEncoder().encode(message);

        const encrypted = await window.crypto.subtle.encrypt(
            {
                name: 'AES-GCM',
                iv: iv
            },
            aesKey,
            messageData
        );

        const exportedKey = await window.crypto.subtle.exportKey('raw', aesKey);

        return {
            encryptedContent: this.arrayBufferToBase64(encrypted),
            aesKey: this.arrayBufferToBase64(exportedKey),
            iv: this.arrayBufferToBase64(iv)
        };
    }

    /**
     * Decrypt message content with AES-256-GCM
     */
    async decryptMessage(encryptedContent, aesKeyBase64, ivBase64) {
        const aesKeyData = this.base64ToArrayBuffer(aesKeyBase64);
        const iv = this.base64ToArrayBuffer(ivBase64);
        const encrypted = this.base64ToArrayBuffer(encryptedContent);

        const aesKey = await window.crypto.subtle.importKey(
            'raw',
            aesKeyData,
            'AES-GCM',
            true,
            ['decrypt']
        );

        const decrypted = await window.crypto.subtle.decrypt(
            {
                name: 'AES-GCM',
                iv: iv
            },
            aesKey,
            encrypted
        );

        return new TextDecoder().decode(decrypted);
    }

    /**
     * Encrypt AES key with recipient's public key (RSA-OAEP)
     */
    async encryptKeyForRecipient(aesKeyBase64, recipientPublicKeyBase64) {
        const recipientPublicKey = await this.importPublicKey(recipientPublicKeyBase64);
        const aesKeyData = this.base64ToArrayBuffer(aesKeyBase64);

        const encrypted = await window.crypto.subtle.encrypt(
            {
                name: 'RSA-OAEP'
            },
            recipientPublicKey,
            aesKeyData
        );

        return this.arrayBufferToBase64(encrypted);
    }

    /**
     * Decrypt AES key with private key (RSA-OAEP)
     */
    async decryptKey(encryptedKeyBase64, privateKey) {
        const encryptedKey = this.base64ToArrayBuffer(encryptedKeyBase64);

        const decrypted = await window.crypto.subtle.decrypt(
            {
                name: 'RSA-OAEP'
            },
            privateKey,
            encryptedKey
        );

        return this.arrayBufferToBase64(decrypted);
    }

    /**
     * Complete message encryption (content + key)
     */
    async encryptMessageComplete(message, recipientPublicKeyBase64) {
        // 1. Encrypt message with AES-256-GCM
        const { encryptedContent, aesKey, iv } = await this.encryptMessage(message);

        // 2. Encrypt AES key with recipient's public key
        const encryptedKey = await this.encryptKeyForRecipient(aesKey, recipientPublicKeyBase64);

        return {
            encryptedContent: encryptedContent + '.' + iv,
            encryptedKey: encryptedKey
        };
    }

    /**
     * Complete message decryption
     */
    async decryptMessageComplete(encryptedContentWithIv, encryptedKey, privateKey) {
        // 1. Split encrypted content and IV
        const [encryptedContent, iv] = encryptedContentWithIv.split('.');

        // 2. Decrypt AES key with private key
        const aesKey = await this.decryptKey(encryptedKey, privateKey);

        // 3. Decrypt message with AES key
        return await this.decryptMessage(encryptedContent, aesKey, iv);
    }

    /**
     * Save encrypted private key to localStorage
     */
    saveEncryptedPrivateKey(encryptedData) {
        localStorage.setItem('dropcrypt_private_key', JSON.stringify(encryptedData));
    }

    /**
     * Load encrypted private key from localStorage
     */
    loadEncryptedPrivateKey() {
        const data = localStorage.getItem('dropcrypt_private_key');
        return data ? JSON.parse(data) : null;
    }

    /**
     * Clear all stored keys
     */
    clearKeys() {
        localStorage.removeItem('dropcrypt_private_key');
        localStorage.removeItem('dropcrypt_session');
        localStorage.removeItem('dropcrypt_anonymous_id');
        this.keyPair = null;
        this.privateKeyEncrypted = null;
    }

    /**
     * Helper: ArrayBuffer to Base64
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }

    /**
     * Helper: Base64 to ArrayBuffer
     */
    base64ToArrayBuffer(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    /**
     * Get current private key (decrypt from storage if needed)
     */
    async getPrivateKey(password) {
        const encryptedData = this.loadEncryptedPrivateKey();
        
        if (!encryptedData) {
            throw new Error('No private key found. Please log in.');
        }

        const privateKeyBase64 = await this.decryptPrivateKey(encryptedData, password);
        return await this.importPrivateKey(privateKeyBase64);
    }
}

// Export for use in other scripts
window.CryptoHelper = CryptoHelper;

