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

        // Generate access token only, content is already encrypted by client
        $accessToken = $this->encryptionService->generateAccessToken();

        $message = new Message();
        $message->setEncryptedContent($data['content']); // Store already encrypted content
        $message->setAccessToken($accessToken);
        $message->setExpiresAt(new \DateTimeImmutable('+' . $data['expiresIn']));

        $errors = $this->validator->validate($message);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $this->json([
            'accessToken' => $message->getAccessToken(),
            'expiresAt' => $message->getExpiresAt()->format('c')
        ]);
    }

    #[Route('/view/{accessToken}', name: 'api_messages_view', methods: ['GET'])]
    public function view(string $accessToken): JsonResponse
    {
        $message = $this->entityManager->getRepository(Message::class)->findOneBy(['accessToken' => $accessToken]);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found or already expired'], Response::HTTP_NOT_FOUND);
        }

        if ($message->isExpired()) {
            $this->entityManager->remove($message);
            $this->entityManager->flush();
            return new JsonResponse(['error' => 'Message has expired'], Response::HTTP_GONE);
        }

        if ($message->isViewed()) {
            return new JsonResponse(['error' => 'Message has already been viewed'], Response::HTTP_GONE);
        }

        // Mark message as viewed and return encrypted content
        $message->setIsViewed(true);
        $this->entityManager->flush();

        return new JsonResponse([
            'content' => $message->getEncryptedContent(),
            'expiresAt' => $message->getExpiresAt()->format('c')
        ]);
    }
} 