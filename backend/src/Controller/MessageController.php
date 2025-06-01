<?php

namespace App\Controller;

use App\Entity\Message;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/messages')]
class MessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EncryptionService $encryptionService,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/create', name: 'message_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['content']) || !isset($data['expiresIn'])) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        // Generate a random encryption key
        $encryptionKey = bin2hex(random_bytes(32));
        $encryptedContent = $this->encryptionService->encrypt($data['content'], $encryptionKey);
        $accessToken = $this->encryptionService->generateAccessToken();
        $encryptionKeyHash = hash('sha256', $encryptionKey);

        $message = new Message();
        $message->setEncryptedContent($encryptedContent);
        $message->setAccessToken($accessToken);
        $message->setEncryptionKeyHash($encryptionKeyHash);
        $message->setExpiresAt(new \DateTimeImmutable('+' . $data['expiresIn']));

        $errors = $this->validator->validate($message);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $this->json([
            'accessToken' => $message->getAccessToken(),
            'token' => $encryptionKey,
            'expiresAt' => $message->getExpiresAt()->format('c')
        ]);
    }

    #[Route('/view/{accessToken}', name: 'api_messages_view', methods: ['GET'])]
    public function view(string $accessToken, Request $request): JsonResponse
    {
        $message = $this->entityManager->getRepository(Message::class)->findOneBy(['accessToken' => $accessToken]);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found or already expired'], Response::HTTP_NOT_FOUND);
        }

        $key = $request->query->get('token');

        if (!$key) {
            return new JsonResponse(['error' => 'Encryption key is required'], Response::HTTP_BAD_REQUEST);
        }

        $keyHash = hash('sha256', $key);
        if ($keyHash !== $message->getEncryptionKeyHash()) {
            return new JsonResponse(['error' => 'Invalid encryption key'], Response::HTTP_BAD_REQUEST);
        }

        $decryptedContent = $this->encryptionService->decrypt($message->getEncryptedContent(), $key);

        return new JsonResponse(['content' => $decryptedContent]);
    }
} 