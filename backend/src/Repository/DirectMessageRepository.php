<?php

namespace App\Repository;

use App\Entity\DirectMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DirectMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DirectMessage::class);
    }

    public function findByMessageId(string $messageId): ?DirectMessage
    {
        return $this->findOneBy(['messageId' => $messageId, 'isDeleted' => false]);
    }

    public function findInbox(string $userId, int $page = 1, int $limit = 20, ?\DateTime $since = null): array
    {
        $qb = $this->createQueryBuilder('dm')
            ->where('dm.recipientId = :userId')
            ->andWhere('dm.isDeleted = :isDeleted')
            ->setParameter('userId', $userId)
            ->setParameter('isDeleted', false)
            ->orderBy('dm.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($since) {
            $qb->andWhere('dm.createdAt > :since')
               ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }

    public function findSent(string $userId, int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('dm')
            ->where('dm.senderId = :userId')
            ->andWhere('dm.isDeleted = :isDeleted')
            ->setParameter('userId', $userId)
            ->setParameter('isDeleted', false)
            ->orderBy('dm.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findThread(string $user1Id, string $user2Id, int $page = 1, int $limit = 50): array
    {
        return $this->createQueryBuilder('dm')
            ->where('(dm.senderId = :user1 AND dm.recipientId = :user2) OR (dm.senderId = :user2 AND dm.recipientId = :user1)')
            ->andWhere('dm.isDeleted = :isDeleted')
            ->setParameter('user1', $user1Id)
            ->setParameter('user2', $user2Id)
            ->setParameter('isDeleted', false)
            ->orderBy('dm.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findThreads(string $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT 
                CASE 
                    WHEN sender_id = :userId THEN recipient_id
                    ELSE sender_id
                END as other_user_id,
                MAX(created_at) as last_message_at,
                COUNT(*) as total_messages,
                SUM(CASE WHEN recipient_id = :userId AND is_read = 0 THEN 1 ELSE 0 END) as unread_count
            FROM direct_messages
            WHERE (sender_id = :userId OR recipient_id = :userId)
            AND is_deleted = 0
            GROUP BY other_user_id
            ORDER BY last_message_at DESC
        ';
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['userId' => $userId]);
        
        return $result->fetchAllAssociative();
    }

    public function getUnreadCount(string $userId): int
    {
        return (int) $this->createQueryBuilder('dm')
            ->select('COUNT(dm.id)')
            ->where('dm.recipientId = :userId')
            ->andWhere('dm.isRead = :isRead')
            ->andWhere('dm.isDeleted = :isDeleted')
            ->setParameter('userId', $userId)
            ->setParameter('isRead', false)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteExpiredMessages(): int
    {
        $qb = $this->createQueryBuilder('dm')
            ->delete()
            ->where('dm.expiresAt < :now')
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->execute();
    }
}

