<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByAccessToken(string $accessToken): ?Message
    {
        return $this->findOneBy(['accessToken' => $accessToken]);
    }

    public function deleteExpiredMessages(): void
    {
        $qb = $this->createQueryBuilder('m')
            ->delete()
            ->where('m.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable());

        $qb->getQuery()->execute();
    }
} 