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

#[Route('/message')]
class MessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository
    ) {
    }

    #[Route('/', name: 'app_message_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($message);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_message_show', ['accessToken' => $message->getAccessToken()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('message/create.html.twig', [
            'message' => $message,
            'form' => $form,
        ]);
    }

    #[Route('/{accessToken}', name: 'app_message_show', methods: ['GET'])]
    public function show(string $accessToken): Response
    {
        $message = $this->messageRepository->findOneBy(['accessToken' => $accessToken]);
        
        if (!$message) {
            throw $this->createNotFoundException('Message not found');
        }

        return $this->render('message/show.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/api/create', name: 'app_message_api_create', methods: ['POST'])]
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

    #[Route('/api/{accessToken}', name: 'app_message_api_show', methods: ['GET'])]
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
} 