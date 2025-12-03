<?php

namespace App\Controller;

use App\Entity\AnonymousUser;
use App\Repository\AnonymousUserRepository;
use App\Repository\UserSessionRepository;
use App\Service\PasswordGeneratorService;
use App\Service\RecoveryPhraseService;
use App\Service\SessionAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/accounts')]
class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AnonymousUserRepository $userRepository,
        private UserSessionRepository $sessionRepository,
        private PasswordGeneratorService $passwordGenerator,
        private RecoveryPhraseService $recoveryPhraseService,
        private SessionAuthService $authService
    ) {}

    /**
     * POST /api/accounts/create
     * Generate anonymous account credentials
     */
    #[Route('/create', name: 'api_account_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $maxAttempts = 10;
        $attempt = 0;
        
        // Generate unique credentials
        do {
            $attempt++;
            
            $password = $this->passwordGenerator->generate();
            $recoveryPhrase = $this->recoveryPhraseService->generate(24);
            $recoveryPhraseHash = $this->recoveryPhraseService->hash($recoveryPhrase);
            
            // Check uniqueness
            $hashExists = $this->userRepository->recoveryPhraseHashExists($recoveryPhraseHash);
            
            if (!$hashExists) {
                break;
            }
            
        } while ($attempt < $maxAttempts);
        
        if ($attempt >= $maxAttempts) {
            return new JsonResponse([
                'error' => 'Unable to generate unique recovery phrase'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        // Generate unique anonymous ID
        $idAttempt = 0;
        $user = new AnonymousUser();
        
        while ($this->userRepository->anonymousIdExists($user->getAnonymousId())) {
            $user->setAnonymousId('usr_' . bin2hex(random_bytes(8)));
            $idAttempt++;
            
            if ($idAttempt >= $maxAttempts) {
                return new JsonResponse([
                    'error' => 'Unable to generate unique anonymous ID'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        
        $user->setPasswordHash($passwordHash);
        $user->setRecoveryPhraseHash($recoveryPhraseHash);
        $user->setPublicKey(''); // Will be set in finalize step
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'anonymousId' => $user->getAnonymousId(),
            'password' => $password,
            'recoveryPhrase' => $recoveryPhrase
        ], Response::HTTP_CREATED);
    }

    /**
     * POST /api/accounts/finalize
     * Store public key after client-side key generation
     */
    #[Route('/finalize', name: 'api_account_finalize', methods: ['POST'])]
    public function finalize(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $anonymousId = $data['anonymousId'] ?? null;
        $publicKey = $data['publicKey'] ?? null;
        
        if (!$anonymousId || !$publicKey) {
            return new JsonResponse([
                'error' => 'Missing anonymousId or publicKey'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $user = $this->userRepository->findByAnonymousId($anonymousId);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $user->setPublicKey($publicKey);
        $this->entityManager->flush();
        
        // Create session
        $session = $this->authService->createSession($user);
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'sessionToken' => $session->getSessionToken(),
            'expiresAt' => $session->getExpiresAt()->format('c')
        ]);
    }

    /**
     * POST /api/accounts/login
     * Authenticate user
     */
    #[Route('/login', name: 'api_account_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $anonymousId = $data['anonymousId'] ?? null;
        $password = $data['password'] ?? null;
        
        if (!$anonymousId || !$password) {
            return new JsonResponse([
                'error' => 'Missing anonymousId or password'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $user = $this->userRepository->findByAnonymousId($anonymousId);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        if (!password_verify($password, $user->getPasswordHash())) {
            return new JsonResponse([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        // Create session
        $session = $this->authService->createSession($user);
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'sessionToken' => $session->getSessionToken(),
            'expiresAt' => $session->getExpiresAt()->format('c'),
            'publicKey' => $user->getPublicKey(),
            'anonymousId' => $user->getAnonymousId()
        ]);
    }

    /**
     * POST /api/accounts/logout
     * End current session
     */
    #[Route('/logout', name: 'api_account_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $token = $this->authService->getSessionToken($request);
        
        if (!$token) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $session = $this->sessionRepository->findByToken($token);
        
        if ($session) {
            $this->entityManager->remove($session);
            $this->entityManager->flush();
        }
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * GET /api/accounts/me
     * Get current user information
     */
    #[Route('/me', name: 'api_account_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        return new JsonResponse([
            'anonymousId' => $user->getAnonymousId(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'messagesSent' => $user->getMessagesSentCount(),
            'messagesReceived' => $user->getMessagesReceivedCount()
        ]);
    }

    /**
     * PUT /api/accounts/password
     * Change password
     */
    #[Route('/password', name: 'api_account_password', methods: ['PUT'])]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $currentPassword = $data['currentPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;
        
        if (!$currentPassword || !$newPassword) {
            return new JsonResponse([
                'error' => 'Missing currentPassword or newPassword'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        if (!password_verify($currentPassword, $user->getPasswordHash())) {
            return new JsonResponse([
                'error' => 'Current password is incorrect'
            ], Response::HTTP_FORBIDDEN);
        }
        
        if (strlen($newPassword) < 8) {
            return new JsonResponse([
                'error' => 'New password must be at least 8 characters'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $user->setPasswordHash($newPasswordHash);
        
        // Invalidate all other sessions
        $currentToken = $this->authService->getSessionToken($request);
        $this->sessionRepository->deleteOtherSessions($user->getAnonymousId(), $currentToken);
        
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * POST /api/accounts/recover
     * Recover account with recovery phrase
     */
    #[Route('/recover', name: 'api_account_recover', methods: ['POST'])]
    public function recover(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $recoveryPhrase = $data['recoveryPhrase'] ?? null;
        $newPassword = $data['newPassword'] ?? null;
        
        if (!$recoveryPhrase || !$newPassword) {
            return new JsonResponse([
                'error' => 'Missing recoveryPhrase or newPassword'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Validate recovery phrase format
        if (!$this->recoveryPhraseService->validate($recoveryPhrase)) {
            return new JsonResponse([
                'error' => 'Invalid recovery phrase format (must be 24 words)'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $recoveryPhraseHash = $this->recoveryPhraseService->hash($recoveryPhrase);
        
        $user = $this->userRepository->findByRecoveryPhraseHash($recoveryPhraseHash);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Invalid recovery phrase'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $user->setPasswordHash($newPasswordHash);
        
        // Invalidate all sessions
        $this->sessionRepository->deleteAllUserSessions($user->getAnonymousId());
        
        $this->entityManager->flush();
        
        // Create new session
        $session = $this->authService->createSession($user);
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'anonymousId' => $user->getAnonymousId(),
            'sessionToken' => $session->getSessionToken(),
            'message' => 'Account recovered successfully'
        ]);
    }

    /**
     * DELETE /api/accounts/me
     * Delete account
     */
    #[Route('/me', name: 'api_account_delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $password = $data['password'] ?? null;
        $confirmation = $data['confirmation'] ?? null;
        
        if (!$password || $confirmation !== 'DELETE MY ACCOUNT') {
            return new JsonResponse([
                'error' => 'Invalid confirmation'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        if (!password_verify($password, $user->getPasswordHash())) {
            return new JsonResponse([
                'error' => 'Incorrect password'
            ], Response::HTTP_FORBIDDEN);
        }
        
        // Mark as inactive (soft delete)
        $user->setIsActive(false);
        
        // Delete all sessions
        $this->sessionRepository->deleteAllUserSessions($user->getAnonymousId());
        
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }
}

