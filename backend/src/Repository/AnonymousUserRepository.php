<?php

namespace App\Repository;

use App\Entity\AnonymousUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AnonymousUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnonymousUser::class);
    }

    public function findByAnonymousId(string $anonymousId): ?AnonymousUser
    {
        return $this->findOneBy(['anonymousId' => $anonymousId, 'isActive' => true]);
    }

    public function findByRecoveryPhraseHash(string $recoveryPhraseHash): ?AnonymousUser
    {
        return $this->findOneBy(['recoveryPhraseHash' => $recoveryPhraseHash, 'isActive' => true]);
    }

    public function anonymousIdExists(string $anonymousId): bool
    {
        return $this->count(['anonymousId' => $anonymousId]) > 0;
    }

    public function recoveryPhraseHashExists(string $recoveryPhraseHash): bool
    {
        return $this->count(['recoveryPhraseHash' => $recoveryPhraseHash]) > 0;
    }
}

