<?php

namespace App\Service;

class EncryptionService
{
    private string $cipher = 'aes-256-gcm';

    // Encrypt data with a provided key
    public function encrypt(string $data, string $key): string
    {
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $tag = '';
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        return base64_encode($iv . $tag . $encrypted);
    }

    // Decrypt data with a provided key
    public function decrypt(string $data, string $key): string
    {
        $data = base64_decode($data);
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $taglen = 16;
        $iv = substr($data, 0, $ivlen);
        $tag = substr($data, $ivlen, $taglen);
        $encrypted = substr($data, $ivlen + $taglen);
        return openssl_decrypt(
            $encrypted,
            $this->cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }

    public function generateAccessToken(): string
    {
        return bin2hex(random_bytes(32));
    }
} 