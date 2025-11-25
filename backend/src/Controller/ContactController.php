<?php

namespace App\Controller;

use App\Entity\UserContact;
use App\Repository\AnonymousUserRepository;
use App\Repository\UserContactRepository;
use App\Service\SessionAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/contacts')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserContactRepository $contactRepository,
        private AnonymousUserRepository $userRepository,
        private SessionAuthService $authService
    ) {}

    /**
     * GET /api/contacts
     * List all contacts
     */
    #[Route('', name: 'api_contacts_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $contacts = $this->contactRepository->findAllByUserId($user->getAnonymousId());
        
        $formattedContacts = [];
        
        foreach ($contacts as $contact) {
            $contactUser = $this->userRepository->findByAnonymousId($contact->getContactId());
            
            $formattedContacts[] = [
                'id' => $contact->getId(),
                'userId' => $contact->getContactId(),
                'nickname' => $contact->getNickname(),
                'addedAt' => $contact->getAddedAt()->format('c'),
                'isActive' => $contactUser?->getIsActive() ?? false
            ];
        }
        
        return new JsonResponse([
            'contacts' => $formattedContacts,
            'count' => count($formattedContacts)
        ]);
    }

    /**
     * POST /api/contacts
     * Add new contact
     */
    #[Route('', name: 'api_contacts_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $contactId = $data['userId'] ?? null;
        $nickname = $data['nickname'] ?? null;
        
        if (!$contactId) {
            return new JsonResponse([
                'error' => 'Missing userId'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Cannot add yourself
        if ($contactId === $user->getAnonymousId()) {
            return new JsonResponse([
                'error' => 'Cannot add yourself as contact'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Check if user exists
        $contactUser = $this->userRepository->findByAnonymousId($contactId);
        
        if (!$contactUser || !$contactUser->getIsActive()) {
            return new JsonResponse([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Check if contact already exists
        $existing = $this->contactRepository->findContact($user->getAnonymousId(), $contactId);
        
        if ($existing) {
            return new JsonResponse([
                'error' => 'Contact already exists'
            ], Response::HTTP_CONFLICT);
        }
        
        // Create contact
        $contact = new UserContact();
        $contact->setUserId($user->getAnonymousId());
        $contact->setContactId($contactId);
        $contact->setNickname($nickname);
        
        $this->entityManager->persist($contact);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'id' => $contact->getId(),
            'userId' => $contact->getContactId(),
            'nickname' => $contact->getNickname(),
            'addedAt' => $contact->getAddedAt()->format('c')
        ], Response::HTTP_CREATED);
    }

    /**
     * PUT /api/contacts/{id}
     * Update contact nickname
     */
    #[Route('/{id}', name: 'api_contacts_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $contact = $this->contactRepository->find($id);
        
        if (!$contact || $contact->getUserId() !== $user->getAnonymousId()) {
            return new JsonResponse([
                'error' => 'Contact not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $nickname = $data['nickname'] ?? null;
        
        if ($nickname !== null) {
            $contact->setNickname($nickname);
            $this->entityManager->flush();
        }
        
        return new JsonResponse([
            'id' => $contact->getId(),
            'userId' => $contact->getContactId(),
            'nickname' => $contact->getNickname(),
            'addedAt' => $contact->getAddedAt()->format('c')
        ]);
    }

    /**
     * DELETE /api/contacts/{id}
     * Remove contact
     */
    #[Route('/{id}', name: 'api_contacts_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $contact = $this->contactRepository->find($id);
        
        if (!$contact || $contact->getUserId() !== $user->getAnonymousId()) {
            return new JsonResponse([
                'error' => 'Contact not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($contact);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Contact removed successfully'
        ]);
    }

    /**
     * GET /api/contacts/{id}
     * Get single contact details
     */
    #[Route('/{id}', name: 'api_contacts_show', methods: ['GET'])]
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $contact = $this->contactRepository->find($id);
        
        if (!$contact || $contact->getUserId() !== $user->getAnonymousId()) {
            return new JsonResponse([
                'error' => 'Contact not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $contactUser = $this->userRepository->findByAnonymousId($contact->getContactId());
        
        return new JsonResponse([
            'id' => $contact->getId(),
            'userId' => $contact->getContactId(),
            'nickname' => $contact->getNickname(),
            'addedAt' => $contact->getAddedAt()->format('c'),
            'isActive' => $contactUser?->getIsActive() ?? false,
            'publicKey' => $contactUser?->getPublicKey()
        ]);
    }
}

