<?php

namespace App\Controller;

use App\Repository\UserPreferenceRepository;
use App\Service\SessionAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/preferences')]
class PreferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPreferenceRepository $preferenceRepository,
        private SessionAuthService $authService
    ) {}

    /**
     * GET /api/preferences
     * Get user preferences
     */
    #[Route('', name: 'api_preferences_get', methods: ['GET'])]
    public function get(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $preferences = $this->preferenceRepository->findByUserId($user->getAnonymousId());
        
        if (!$preferences) {
            return new JsonResponse([
                'error' => 'Preferences not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        return new JsonResponse([
            'sendReadReceipts' => $preferences->getSendReadReceipts(),
            'receiveReadReceipts' => $preferences->getReceiveReadReceipts()
        ]);
    }

    /**
     * PUT /api/preferences
     * Update user preferences
     */
    #[Route('', name: 'api_preferences_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $preferences = $this->preferenceRepository->findByUserId($user->getAnonymousId());
        
        if (!$preferences) {
            return new JsonResponse([
                'error' => 'Preferences not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['sendReadReceipts'])) {
            $preferences->setSendReadReceipts((bool) $data['sendReadReceipts']);
        }
        
        if (isset($data['receiveReadReceipts'])) {
            $preferences->setReceiveReadReceipts((bool) $data['receiveReadReceipts']);
        }
        
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'sendReadReceipts' => $preferences->getSendReadReceipts(),
            'receiveReadReceipts' => $preferences->getReceiveReadReceipts()
        ]);
    }
}

