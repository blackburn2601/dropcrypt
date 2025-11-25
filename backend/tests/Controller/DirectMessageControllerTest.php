<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DirectMessageControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testSendMessageBetweenUsers(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Send message from user1 to user2
        $this->client->request('POST', '/api/direct-messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $user2Id,
            'encryptedContent' => 'encrypted_content_test.iv_test',
            'encryptedKey' => 'encrypted_key_test',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('messageId', $data);
        $this->assertArrayHasKey('sentAt', $data);
        $this->assertArrayHasKey('expiresAt', $data);
        $this->assertMatchesRegularExpression('/^msg_[a-f0-9]{24}$/', $data['messageId']);
    }

    public function testSendMessageWithoutAuth(): void
    {
        $this->client->request('POST', '/api/direct-messages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => 'usr_test123',
            'encryptedContent' => 'test',
            'encryptedKey' => 'test',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSendMessageToNonexistentUser(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();

        $this->client->request('POST', '/api/direct-messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => 'usr_nonexistent1234',
            'encryptedContent' => 'test',
            'encryptedKey' => 'test',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetInbox(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Send message from user1 to user2
        $this->client->request('POST', '/api/direct-messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $user2Id,
            'encryptedContent' => 'encrypted_content.iv',
            'encryptedKey' => 'encrypted_key',
        ]));

        // User2 checks inbox
        $this->client->request('GET', '/api/direct-messages/inbox', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user2Token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('messages', $data);
        $this->assertArrayHasKey('page', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertCount(1, $data['messages']);

        $message = $data['messages'][0];
        $this->assertEquals($user1Id, $message['senderId']);
        $this->assertEquals('encrypted_content.iv', $message['encryptedContent']);
        $this->assertEquals('encrypted_key', $message['encryptedKey']);
        $this->assertFalse($message['isRead']);
    }

    public function testGetSentMessages(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Send message
        $this->client->request('POST', '/api/direct-messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $user2Id,
            'encryptedContent' => 'test_content.iv',
            'encryptedKey' => 'test_key',
        ]));

        // User1 checks sent messages
        $this->client->request('GET', '/api/direct-messages/sent', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['messages']);
        $this->assertEquals($user2Id, $data['messages'][0]['recipientId']);
    }

    public function testGetThreads(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();
        [$user3Id, $user3Token] = $this->createAndFinalizeAccount();

        // Send messages to create threads
        $this->sendMessage($user1Token, $user2Id);
        $this->sendMessage($user1Token, $user3Id);
        $this->sendMessage($user2Token, $user1Id);

        // User1 gets threads
        $this->client->request('GET', '/api/direct-messages/threads', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('threads', $data);
        $this->assertCount(2, $data['threads']); // Conversations with user2 and user3
    }

    public function testGetThreadWithSpecificUser(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Send messages back and forth
        $this->sendMessage($user1Token, $user2Id);
        $this->sendMessage($user2Token, $user1Id);
        $this->sendMessage($user1Token, $user2Id);

        // User1 gets thread with user2
        $this->client->request('GET', '/api/direct-messages/thread/' . $user2Id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(3, $data['messages']);
    }

    public function testMarkMessageAsRead(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Send message
        $this->client->request('POST', '/api/direct-messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $user2Id,
            'encryptedContent' => 'test.iv',
            'encryptedKey' => 'key',
        ]));

        $sendData = json_decode($this->client->getResponse()->getContent(), true);
        $messageId = $sendData['messageId'];

        // User2 marks as read
        $this->client->request('PUT', '/api/direct-messages/' . $messageId . '/read', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user2Token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('readAt', $data);
    }

    public function testDeleteMessage(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Send message
        $this->client->request('POST', '/api/direct-messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $user2Id,
            'encryptedContent' => 'test.iv',
            'encryptedKey' => 'key',
        ]));

        $sendData = json_decode($this->client->getResponse()->getContent(), true);
        $messageId = $sendData['messageId'];

        // User1 (sender) deletes message
        $this->client->request('DELETE', '/api/direct-messages/' . $messageId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $this->assertResponseIsSuccessful();

        // Verify message no longer in inbox
        $this->client->request('GET', '/api/direct-messages/inbox', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user2Token,
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $data['messages']);
    }

    public function testRecipientCannotDeleteMessage(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Send message
        $this->client->request('POST', '/api/direct-messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $user2Id,
            'encryptedContent' => 'test.iv',
            'encryptedKey' => 'key',
        ]));

        $sendData = json_decode($this->client->getResponse()->getContent(), true);
        $messageId = $sendData['messageId'];

        // User2 (recipient) tries to delete
        $this->client->request('DELETE', '/api/direct-messages/' . $messageId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user2Token,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testPollingEndpoint(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        $since = (new \DateTime())->format('c');

        // Send message after timestamp
        sleep(1);
        $this->sendMessage($user1Token, $user2Id);

        // User2 polls for new messages
        $this->client->request('GET', '/api/direct-messages/poll?since=' . urlencode($since), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user2Token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('unreadCount', $data);
        $this->assertArrayHasKey('newMessages', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertEquals(1, $data['unreadCount']);
        $this->assertCount(1, $data['newMessages']);
    }

    public function testUserStatisticsUpdatedAfterMessaging(): void
    {
        [$user1Id, $user1Token] = $this->createAndFinalizeAccount();
        [$user2Id, $user2Token] = $this->createAndFinalizeAccount();

        // Send 2 messages from user1 to user2
        $this->sendMessage($user1Token, $user2Id);
        $this->sendMessage($user1Token, $user2Id);

        // Check user1 stats
        $this->client->request('GET', '/api/accounts/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ]);

        $user1Data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $user1Data['messagesSent']);
        $this->assertEquals(0, $user1Data['messagesReceived']);

        // Check user2 stats
        $this->client->request('GET', '/api/accounts/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user2Token,
        ]);

        $user2Data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(0, $user2Data['messagesSent']);
        $this->assertEquals(2, $user2Data['messagesReceived']);
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

    private function sendMessage(string $senderToken, string $recipientId): void
    {
        $this->client->request('POST', '/api/direct-messages', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $senderToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $recipientId,
            'encryptedContent' => 'content_' . bin2hex(random_bytes(8)) . '.iv',
            'encryptedKey' => 'key_' . bin2hex(random_bytes(8)),
        ]));
    }
}

