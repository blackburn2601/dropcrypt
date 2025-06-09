class MessageEncryption {
    static async generateKey() {
        const key = await window.crypto.subtle.generateKey(
            {
                name: "AES-GCM",
                length: 256
            },
            true,
            ["encrypt", "decrypt"]
        );
        return key;
    }

    static async exportKey(key) {
        const exported = await window.crypto.subtle.exportKey(
            "raw",
            key
        );
        return btoa(String.fromCharCode(...new Uint8Array(exported)));
    }

    static async importKey(keyString) {
        const keyData = Uint8Array.from(atob(keyString), c => c.charCodeAt(0));
        return window.crypto.subtle.importKey(
            "raw",
            keyData,
            {
                name: "AES-GCM",
                length: 256
            },
            true,
            ["encrypt", "decrypt"]
        );
    }

    static async encrypt(message, key) {
        const encoder = new TextEncoder();
        const data = encoder.encode(message);
        
        const iv = window.crypto.getRandomValues(new Uint8Array(12));
        
        const encryptedData = await window.crypto.subtle.encrypt(
            {
                name: "AES-GCM",
                iv: iv
            },
            key,
            data
        );

        const encryptedArray = new Uint8Array(encryptedData);
        const combined = new Uint8Array(iv.length + encryptedArray.length);
        combined.set(iv);
        combined.set(encryptedArray, iv.length);

        return btoa(String.fromCharCode(...combined));
    }

    static async decrypt(encryptedData, key) {
        const combined = Uint8Array.from(atob(encryptedData), c => c.charCodeAt(0));
        
        const iv = combined.slice(0, 12);
        const data = combined.slice(12);

        const decrypted = await window.crypto.subtle.decrypt(
            {
                name: "AES-GCM",
                iv: iv
            },
            key,
            data
        );

        const decoder = new TextDecoder();
        return decoder.decode(decrypted);
    }

    static async hashKey(keyString) {
        const encoder = new TextEncoder();
        const data = encoder.encode(keyString);
        const hashBuffer = await window.crypto.subtle.digest('SHA-256', data);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }
} 