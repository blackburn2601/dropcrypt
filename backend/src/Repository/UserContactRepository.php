<?php

namespace App\Repository;

use App\Entity\UserContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserContact::class);
    }

    public function findAllByUserId(string $userId): array
    {
        return $this->createQueryBuilder('uc')
            ->where('uc.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('uc.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findContact(string $userId, string $contactId): ?UserContact
    {
        return $this->findOneBy([
            'userId' => $userId,
            'contactId' => $contactId
        ]);
    }

    public function contactExists(string $userId, string $contactId): bool
    {
        return $this->count([
            'userId' => $userId,
            'contactId' => $contactId
        ]) > 0;
    }

    public function countContacts(string $userId): int
    {
        return $this->count(['userId' => $userId]);
    }
}

