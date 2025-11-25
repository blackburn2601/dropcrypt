<?php

namespace App\Service;

use App\Entity\AnonymousUser;
use App\Entity\UserSession;
use App\Repository\AnonymousUserRepository;
use App\Repository\UserSessionRepository;
use Symfony\Component\HttpFoundation\Request;

class SessionAuthService
{
    public function __construct(
        private UserSessionRepository $sessionRepository,
        private AnonymousUserRepository $userRepository
    ) {}

    /**
     * Get current user from session token in Authorization header
     */
    public function getCurrentUser(Request $request): ?AnonymousUser
    {
        $authHeader = $request->headers->get('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        
        $token = substr($authHeader, 7); // Remove "Bearer " prefix
        
        $session = $this->sessionRepository->findByToken($token);
        
        if (!$session) {
            return null;
        }
        
        return $this->userRepository->findByAnonymousId($session->getUserId());
    }

    /**
     * Get session token from request
     */
    public function getSessionToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        
        return substr($authHeader, 7);
    }

    /**
     * Create new session for user
     */
    public function createSession(AnonymousUser $user): UserSession
    {
        $session = new UserSession();
        $session->setUserId($user->getAnonymousId());
        
        return $session;
    }

    /**
     * Check if request is authenticated
     */
    public function isAuthenticated(Request $request): bool
    {
        return $this->getCurrentUser($request) !== null;
    }
}

