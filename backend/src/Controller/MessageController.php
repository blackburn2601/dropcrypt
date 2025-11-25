<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('')]
class MessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository
    ) {
    }

    #[Route('/messages/create', name: 'app_messages_create_form', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($message);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_messages_view', ['accessToken' => $message->getAccessToken()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('message/create.html.twig', [
            'message' => $message,
            'form' => $form,
        ]);
    }

    #[Route('/messages/view/{accessToken}', name: 'app_messages_view', methods: ['GET'])]
    public function view(string $accessToken): Response
    {
        $message = $this->messageRepository->findOneBy(['accessToken' => $accessToken]);
        
        return $this->render('message/show.html.twig', [
            'message' => $message,
            'accessToken' => $accessToken,
            'error' => $message ? null : 'Message not found',
        ]);
    }

    #[Route('/api/messages', name: 'api_messages_create', methods: ['POST'])]
    public function apiCreate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['content']) || !isset($data['keyHash']) || !isset($data['expiresAt'])) {
            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $message = new Message();
        $message->setContent($data['content']);
        $message->setKeyHash($data['keyHash']);
        $message->setExpiresAt(new \DateTimeImmutable($data['expiresAt']));
        $message->setIsRead(false);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return new JsonResponse([
            'accessToken' => $message->getAccessToken(),
            'expiresAt' => $message->getExpiresAt()->format('c')
        ]);
    }

    #[Route('/api/messages/{accessToken}', name: 'api_messages_show', methods: ['GET'])]
    public function apiShow(string $accessToken, Request $request): JsonResponse
    {
        $message = $this->messageRepository->findOneBy(['accessToken' => $accessToken]);
        
        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], Response::HTTP_NOT_FOUND);
        }

        $keyHash = $request->query->get('keyHash');

        if (!$keyHash || $keyHash !== $message->getKeyHash()) {
            return new JsonResponse(['error' => 'Invalid key hash'], Response::HTTP_FORBIDDEN);
        }

        if ($message->isExpired()) {
            return new JsonResponse(['error' => 'Message has expired'], Response::HTTP_GONE);
        }

        if ($message->isRead()) {
            return new JsonResponse(['error' => 'Message has already been read'], Response::HTTP_GONE);
        }

        $message->setIsRead(true);

        $this->entityManager->flush();

        return new JsonResponse([
            'content' => $message->getContent()
        ]);
    }

    #[Route('/api/messages/{accessToken}', name: 'api_messages_delete', methods: ['DELETE'])]
    public function apiDelete(string $accessToken, Request $request): JsonResponse
    {
        $message = $this->messageRepository->findOneBy(['accessToken' => $accessToken]);
        
        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], Response::HTTP_NOT_FOUND);
        }

        $keyHash = $request->query->get('keyHash');

        if (!$keyHash || $keyHash !== $message->getKeyHash()) {
            return new JsonResponse(['error' => 'Invalid key hash'], Response::HTTP_FORBIDDEN);
        }

        // Allow deletion if message has not been read or if it is expired
        // Prevent deletion only if message has been read AND is not expired
        if (!$message->isRead() && !$message->isExpired()) {
            return new JsonResponse(['error' => 'Cannot delete message: message has been read and is not expired'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($message);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }
} 