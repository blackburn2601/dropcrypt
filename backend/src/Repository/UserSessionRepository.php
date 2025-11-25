<?php

namespace App\Repository;

use App\Entity\UserSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSession::class);
    }

    public function findByToken(string $sessionToken): ?UserSession
    {
        $session = $this->findOneBy(['sessionToken' => $sessionToken]);
        
        if ($session && !$session->isExpired()) {
            return $session;
        }
        
        return null;
    }

    public function findUserSessions(string $userId): array
    {
        return $this->createQueryBuilder('us')
            ->where('us.userId = :userId')
            ->andWhere('us.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTime())
            ->orderBy('us.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteExpiredSessions(): int
    {
        $qb = $this->createQueryBuilder('us')
            ->delete()
            ->where('us.expiresAt < :now')
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->execute();
    }

    public function deleteAllUserSessions(string $userId): int
    {
        $qb = $this->createQueryBuilder('us')
            ->delete()
            ->where('us.userId = :userId')
            ->setParameter('userId', $userId);

        return $qb->getQuery()->execute();
    }

    public function deleteOtherSessions(string $userId, string $currentToken): int
    {
        $qb = $this->createQueryBuilder('us')
            ->delete()
            ->where('us.userId = :userId')
            ->andWhere('us.sessionToken != :currentToken')
            ->setParameter('userId', $userId)
            ->setParameter('currentToken', $currentToken);

        return $qb->getQuery()->execute();
    }
}

