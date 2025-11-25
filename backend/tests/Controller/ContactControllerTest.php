<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ContactControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testAddContact(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // User1 adds user2 as contact
        $this->client->request('POST', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => $user2Id,
            'nickname' => 'Bob',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($user2Id, $data['userId']);
        $this->assertEquals('Bob', $data['nickname']);
    }

    public function testListContacts(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();
        [$user3Id, $user3Token] = $this->createAndFinalizeAccount();

        // Add contacts
        $this->addContact($user1Token, $user2Id, 'Alice');
        $this->addContact($user1Token, $user3Id, 'Charlie');

        // List contacts
        $this->client->request('GET', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('contacts', $data);
        $this->assertCount(2, $data['contacts']);
    }

    public function testUpdateContactNickname(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Add contact
        $this->client->request('POST', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => $user2Id,
            'nickname' => 'OldNickname',
        ]));

        $addData = json_decode($this->client->getResponse()->getContent(), true);
        $contactId = $addData['id'];

        // Update nickname
        $this->client->request('PUT', '/api/contacts/' . $contactId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'nickname' => 'NewNickname',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('NewNickname', $data['nickname']);
    }

    public function testDeleteContact(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Add contact
        $this->client->request('POST', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => $user2Id,
            'nickname' => 'ToDelete',
        ]));

        $addData = json_decode($this->client->getResponse()->getContent(), true);
        $contactId = $addData['id'];

        // Delete contact
        $this->client->request('DELETE', '/api/contacts/' . $contactId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $this->assertResponseIsSuccessful();

        // Verify contact list is empty
        $this->client->request('GET', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $data['contacts']);
    }

    public function testCannotAddSelfAsContact(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();

        $this->client->request('POST', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => $user1Id, // Same as logged in user
            'nickname' => 'Myself',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCannotAddNonexistentUserAsContact(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();

        $this->client->request('POST', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => 'usr_nonexistent123',
            'nickname' => 'Ghost',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetContactDetails(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Add contact
        $this->client->request('POST', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => $user2Id,
            'nickname' => 'DetailTest',
        ]));

        $addData = json_decode($this->client->getResponse()->getContent(), true);
        $contactId = $addData['id'];

        // Get contact details
        $this->client->request('GET', '/api/contacts/' . $contactId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($user2Id, $data['userId']);
        $this->assertEquals('DetailTest', $data['nickname']);
        $this->assertArrayHasKey('publicKey', $data);
        $this->assertTrue($data['isActive']);
    }

    // Helper methods
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
            'publicKey' => 'test_key_' . bin2hex(random_bytes(16)),
        ]));

        $finalizeData = json_decode($this->client->getResponse()->getContent(), true);

        return [$createData['anonymousId'], $finalizeData['sessionToken']];
    }

    private function addContact(string $token, string $contactUserId, string $nickname): void
    {
        $this->client->request('POST', '/api/contacts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => $contactUserId,
            'nickname' => $nickname,
        ]));
    }
}

