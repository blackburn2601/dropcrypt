<?php

namespace App\Controller;

use App\Repository\AnonymousUserRepository;
use App\Service\SessionAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private AnonymousUserRepository $userRepository,
        private SessionAuthService $authService
    ) {}

    /**
     * GET /api/users/{anonymousId}/public-key
     * Get user's public key for encryption
     */
    #[Route('/{anonymousId}/public-key', name: 'api_users_public_key', methods: ['GET'])]
    public function getPublicKey(string $anonymousId, Request $request): JsonResponse
    {
        $currentUser = $this->authService->getCurrentUser($request);
        
        if (!$currentUser) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $user = $this->userRepository->findByAnonymousId($anonymousId);
        
        if (!$user || !$user->getIsActive()) {
            return new JsonResponse([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        return new JsonResponse([
            'anonymousId' => $user->getAnonymousId(),
            'publicKey' => $user->getPublicKey()
        ]);
    }

    /**
     * GET /api/users/{anonymousId}
     * Get minimal user info (exists check)
     */
    #[Route('/{anonymousId}', name: 'api_users_show', methods: ['GET'])]
    public function show(string $anonymousId, Request $request): JsonResponse
    {
        $currentUser = $this->authService->getCurrentUser($request);
        
        if (!$currentUser) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $user = $this->userRepository->findByAnonymousId($anonymousId);
        
        if (!$user || !$user->getIsActive()) {
            return new JsonResponse([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        return new JsonResponse([
            'anonymousId' => $user->getAnonymousId(),
            'exists' => true,
            'isActive' => true
        ]);
    }
}

