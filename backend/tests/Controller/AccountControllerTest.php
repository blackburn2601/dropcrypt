<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AccountControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCreateAccountReturnsCredentials(): void
    {
        $this->client->request('POST', '/api/accounts/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('anonymousId', $data);
        $this->assertArrayHasKey('password', $data);
        $this->assertArrayHasKey('recoveryPhrase', $data);

        // Verify anonymousId format
        $this->assertMatchesRegularExpression('/^usr_[a-f0-9]{16}$/', $data['anonymousId']);

        // Verify password format
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]+-[A-Z][a-z]+-[A-Z][a-z]+-\d{2}$/', $data['password']);

        // Verify recovery phrase has 24 words
        $words = explode(' ', $data['recoveryPhrase']);
        $this->assertCount(24, $words);
    }

    public function testFinalizeAccountWithPublicKey(): void
    {
        // First create account
        $this->client->request('POST', '/api/accounts/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $createData = json_decode($this->client->getResponse()->getContent(), true);

        // Then finalize with public key
        $this->client->request('POST', '/api/accounts/finalize', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => $createData['anonymousId'],
            'publicKey' => 'test_public_key_' . bin2hex(random_bytes(32)),
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('sessionToken', $data);
        $this->assertArrayHasKey('expiresAt', $data);
        $this->assertEquals(128, strlen($data['sessionToken'])); // 64 bytes hex = 128 chars
    }

    public function testLoginWithValidCredentials(): void
    {
        // Create account
        $this->client->request('POST', '/api/accounts/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $createData = json_decode($this->client->getResponse()->getContent(), true);

        // Finalize account
        $this->client->request('POST', '/api/accounts/finalize', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => $createData['anonymousId'],
            'publicKey' => 'test_public_key',
        ]));

        // Login
        $this->client->request('POST', '/api/accounts/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => $createData['anonymousId'],
            'password' => $createData['password'],
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('sessionToken', $data);
        $this->assertArrayHasKey('publicKey', $data);
        $this->assertArrayHasKey('anonymousId', $data);
        $this->assertEquals($createData['anonymousId'], $data['anonymousId']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request('POST', '/api/accounts/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => 'usr_invalid123456789',
            'password' => 'WrongPassword',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetCurrentUserInfo(): void
    {
        // Create and finalize account
        [$anonymousId, $sessionToken] = $this->createAndFinalizeAccount();

        // Get user info
        $this->client->request('GET', '/api/accounts/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $sessionToken,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('anonymousId', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('messagesSent', $data);
        $this->assertArrayHasKey('messagesReceived', $data);
        $this->assertEquals($anonymousId, $data['anonymousId']);
        $this->assertEquals(0, $data['messagesSent']);
        $this->assertEquals(0, $data['messagesReceived']);
    }

    public function testGetCurrentUserInfoWithoutAuth(): void
    {
        $this->client->request('GET', '/api/accounts/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogout(): void
    {
        [$anonymousId, $sessionToken] = $this->createAndFinalizeAccount();

        // Logout
        $this->client->request('POST', '/api/accounts/logout', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $sessionToken,
        ]);

        $this->assertResponseIsSuccessful();

        // Try to access protected route with same token
        $this->client->request('GET', '/api/accounts/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $sessionToken,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAccountRecoveryWithValidRecoveryPhrase(): void
    {
        // Create account
        $this->client->request('POST', '/api/accounts/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $createData = json_decode($this->client->getResponse()->getContent(), true);

        // Finalize
        $this->client->request('POST', '/api/accounts/finalize', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => $createData['anonymousId'],
            'publicKey' => 'test_key',
        ]));

        // Recover account
        $this->client->request('POST', '/api/accounts/recover', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recoveryPhrase' => $createData['recoveryPhrase'],
            'newPassword' => 'NewSecurePassword123',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('anonymousId', $data);
        $this->assertArrayHasKey('sessionToken', $data);
        $this->assertTrue($data['success']);
        $this->assertEquals($createData['anonymousId'], $data['anonymousId']);
    }

    public function testChangePassword(): void
    {
        // Create account
        $this->client->request('POST', '/api/accounts/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $createData = json_decode($this->client->getResponse()->getContent(), true);
        $oldPassword = $createData['password'];

        // Finalize
        $this->client->request('POST', '/api/accounts/finalize', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => $createData['anonymousId'],
            'publicKey' => 'test_key',
        ]));

        $sessionData = json_decode($this->client->getResponse()->getContent(), true);

        // Change password
        $this->client->request('PUT', '/api/accounts/password', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $sessionData['sessionToken'],
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => $oldPassword,
            'newPassword' => 'MyNewSecurePassword123',
        ]));

        $this->assertResponseIsSuccessful();

        // Verify old password doesn't work
        $this->client->request('POST', '/api/accounts/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => $createData['anonymousId'],
            'password' => $oldPassword,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Verify new password works
        $this->client->request('POST', '/api/accounts/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => $createData['anonymousId'],
            'password' => 'MyNewSecurePassword123',
        ]));

        $this->assertResponseIsSuccessful();
    }

    // Helper method
    private function createAndFinalizeAccount(): array
    {
        $this->client->request('POST', '/api/accounts/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $createData = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('POST', '/api/accounts/finalize', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'anonymousId' => $createData['anonymousId'],
            'publicKey' => 'test_public_key_' . bin2hex(random_bytes(16)),
        ]));

        $finalizeData = json_decode($this->client->getResponse()->getContent(), true);

        return [$createData['anonymousId'], $finalizeData['sessionToken']];
    }
}


