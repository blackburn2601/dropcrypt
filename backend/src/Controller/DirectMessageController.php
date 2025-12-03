<?php

namespace App\Controller;

use App\Entity\DirectMessage;
use App\Repository\AnonymousUserRepository;
use App\Repository\DirectMessageRepository;
use App\Repository\UserContactRepository;
use App\Service\SessionAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/direct-messages')]
class DirectMessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DirectMessageRepository $messageRepository,
        private AnonymousUserRepository $userRepository,
        private UserContactRepository $contactRepository,
        private SessionAuthService $authService
    ) {}

    /**
     * POST /api/direct-messages
     * Send encrypted message
     */
    #[Route('', name: 'api_direct_messages_send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $recipientId = $data['recipientId'] ?? null;
        $encryptedContent = $data['encryptedContent'] ?? null;
        $encryptedKeyForRecipient = $data['encryptedKeyForRecipient'] ?? null;
        $encryptedKeyForSender = $data['encryptedKeyForSender'] ?? null;
        
        if (!$recipientId || !$encryptedContent || !$encryptedKeyForRecipient || !$encryptedKeyForSender) {
            return new JsonResponse([
                'error' => 'Missing required fields: recipientId, encryptedContent, encryptedKeyForRecipient, encryptedKeyForSender'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Check recipient exists
        $recipient = $this->userRepository->findByAnonymousId($recipientId);
        
        if (!$recipient || !$recipient->getIsActive()) {
            return new JsonResponse([
                'error' => 'Recipient not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Create message
        $message = new DirectMessage();
        $message->setSenderId($user->getAnonymousId());
        $message->setRecipientId($recipientId);
        $message->setEncryptedContent($encryptedContent);
        $message->setEncryptedKeyForRecipient($encryptedKeyForRecipient);
        $message->setEncryptedKeyForSender($encryptedKeyForSender);
        
        $this->entityManager->persist($message);
        
        // Update user statistics
        $user->setMessagesSentCount($user->getMessagesSentCount() + 1);
        $recipient->setMessagesReceivedCount($recipient->getMessagesReceivedCount() + 1);
        
        $this->entityManager->flush();
        
        return new JsonResponse([
            'messageId' => $message->getMessageId(),
            'sentAt' => $message->getSentAt()->format('c'),
            'expiresAt' => $message->getExpiresAt()->format('c')
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/direct-messages/inbox
     * Get inbox messages (received, paginated, polling-friendly)
     */
    #[Route('/inbox', name: 'api_direct_messages_inbox', methods: ['GET'])]
    public function inbox(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $since = $request->query->get('since'); // ISO 8601 timestamp for polling
        
        $messages = $this->messageRepository->findInbox(
            $user->getAnonymousId(),
            $page,
            $limit,
            $since ? new \DateTime($since) : null
        );
        
        $formattedMessages = [];
        
        foreach ($messages as $message) {
            $sender = $this->userRepository->findByAnonymousId($message->getSenderId());
            
            $formattedMessages[] = [
                'messageId' => $message->getMessageId(),
                'senderId' => $message->getSenderId(),
                'encryptedContent' => $message->getEncryptedContent(),
                'encryptedKey' => $message->getEncryptedKeyForRecipient(), // User is recipient
                'sentAt' => $message->getSentAt()->format('c'),
                'expiresAt' => $message->getExpiresAt()->format('c'),
                'isRead' => $message->getIsRead(),
                'readAt' => $message->getReadAt()?->format('c')
            ];
        }
        
        return new JsonResponse([
            'messages' => $formattedMessages,
            'page' => $page,
            'limit' => $limit,
            'count' => count($formattedMessages)
        ]);
    }

    /**
     * GET /api/direct-messages/sent
     * Get sent messages
     */
    #[Route('/sent', name: 'api_direct_messages_sent', methods: ['GET'])]
    public function sent(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        
        $messages = $this->messageRepository->findSent(
            $user->getAnonymousId(),
            $page,
            $limit
        );
        
        $formattedMessages = [];
        
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'messageId' => $message->getMessageId(),
                'recipientId' => $message->getRecipientId(),
                'encryptedContent' => $message->getEncryptedContent(),
                'sentAt' => $message->getSentAt()->format('c'),
                'expiresAt' => $message->getExpiresAt()->format('c'),
                'isRead' => $message->getIsRead(),
                'readAt' => $message->getReadAt()?->format('c')
            ];
        }
        
        return new JsonResponse([
            'messages' => $formattedMessages,
            'page' => $page,
            'limit' => $limit,
            'count' => count($formattedMessages)
        ]);
    }

    /**
     * GET /api/direct-messages/threads
     * Get unique conversation threads
     */
    #[Route('/threads', name: 'api_direct_messages_threads', methods: ['GET'])]
    public function threads(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $threads = $this->messageRepository->findThreads($user->getAnonymousId());
        
        $formattedThreads = [];
        
        foreach ($threads as $thread) {
            $otherUserId = $thread['other_user_id'];
            $otherUser = $this->userRepository->findByAnonymousId($otherUserId);
            
            // Get contact nickname if exists
            $contact = $this->contactRepository->findContact($user->getAnonymousId(), $otherUserId);
            $nickname = $contact?->getNickname();
            
            $formattedThreads[] = [
                'userId' => $otherUserId,
                'nickname' => $nickname,
                'lastMessageAt' => (new \DateTime($thread['last_message_at']))->format('c'),
                'unreadCount' => (int) $thread['unread_count'],
                'totalMessages' => (int) $thread['total_messages']
            ];
        }
        
        return new JsonResponse([
            'threads' => $formattedThreads,
            'count' => count($formattedThreads)
        ]);
    }

    /**
     * GET /api/direct-messages/thread/{userId}
     * Get conversation thread with specific user
     */
    #[Route('/thread/{userId}', name: 'api_direct_messages_thread', methods: ['GET'])]
    public function thread(string $userId, Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        
        $messages = $this->messageRepository->findThread(
            $user->getAnonymousId(),
            $userId,
            $page,
            $limit
        );
        
        $formattedMessages = [];
        
        foreach ($messages as $message) {
            $isSender = $message->getSenderId() === $user->getAnonymousId();
            
            $formattedMessages[] = [
                'messageId' => $message->getMessageId(),
                'senderId' => $message->getSenderId(),
                'recipientId' => $message->getRecipientId(),
                'encryptedContent' => $message->getEncryptedContent(),
                'encryptedKey' => $isSender 
                    ? $message->getEncryptedKeyForSender()  // Sender uses their key
                    : $message->getEncryptedKeyForRecipient(), // Recipient uses their key
                'sentAt' => $message->getSentAt()->format('c'),
                'expiresAt' => $message->getExpiresAt()->format('c'),
                'isRead' => $message->getIsRead(),
                'readAt' => $message->getReadAt()?->format('c'),
                'isSender' => $isSender
            ];
        }
        
        return new JsonResponse([
            'messages' => $formattedMessages,
            'page' => $page,
            'limit' => $limit,
            'count' => count($formattedMessages)
        ]);
    }

    /**
     * PUT /api/direct-messages/{messageId}/read
     * Mark message as read
     */
    #[Route('/{messageId}/read', name: 'api_direct_messages_mark_read', methods: ['PUT'])]
    public function markRead(string $messageId, Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $message = $this->messageRepository->findByMessageId($messageId);
        
        if (!$message) {
            return new JsonResponse([
                'error' => 'Message not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        if ($message->getRecipientId() !== $user->getAnonymousId()) {
            return new JsonResponse([
                'error' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }
        
        if (!$message->getIsRead()) {
            $message->setIsRead(true);
            $message->setReadAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }
        
        return new JsonResponse([
            'success' => true,
            'readAt' => $message->getReadAt()->format('c')
        ]);
    }

    /**
     * DELETE /api/direct-messages/{messageId}
     * Delete message (only if sender)
     */
    #[Route('/{messageId}', name: 'api_direct_messages_delete', methods: ['DELETE'])]
    public function delete(string $messageId, Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $message = $this->messageRepository->findByMessageId($messageId);
        
        if (!$message) {
            return new JsonResponse([
                'error' => 'Message not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Only sender can delete
        if ($message->getSenderId() !== $user->getAnonymousId()) {
            return new JsonResponse([
                'error' => 'Only the sender can delete this message'
            ], Response::HTTP_FORBIDDEN);
        }
        
        $this->entityManager->remove($message);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * GET /api/direct-messages/poll
     * Polling endpoint for new messages (optimized)
     */
    #[Route('/poll', name: 'api_direct_messages_poll', methods: ['GET'])]
    public function poll(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $since = $request->query->get('since'); // ISO 8601 timestamp
        
        if (!$since) {
            return new JsonResponse([
                'error' => 'Missing "since" parameter'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $sinceDate = new \DateTime($since);
        
        // Get unread count
        $unreadCount = $this->messageRepository->getUnreadCount($user->getAnonymousId());
        
        // Get new messages since timestamp
        $newMessages = $this->messageRepository->findInbox(
            $user->getAnonymousId(),
            1,
            100,
            $sinceDate
        );
        
        $formattedMessages = [];
        
        foreach ($newMessages as $message) {
            $formattedMessages[] = [
                'messageId' => $message->getMessageId(),
                'senderId' => $message->getSenderId(),
                'encryptedContent' => $message->getEncryptedContent(),
                'encryptedKey' => $message->getEncryptedKeyForRecipient(), // Polling for inbox
                'sentAt' => $message->getSentAt()->format('c'),
                'expiresAt' => $message->getExpiresAt()->format('c'),
                'isRead' => $message->getIsRead()
            ];
        }
        
        return new JsonResponse([
            'unreadCount' => $unreadCount,
            'newMessages' => $formattedMessages,
            'timestamp' => (new \DateTime())->format('c')
        ]);
    }
}

