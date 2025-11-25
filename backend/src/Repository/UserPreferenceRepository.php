<?php

namespace App\Repository;

use App\Entity\UserPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPreference::class);
    }

    public function findByUserId(string $userId): ?UserPreference
    {
        return $this->findOneBy(['userId' => $userId]);
    }

    public function getOrCreate(string $userId): UserPreference
    {
        $preference = $this->findByUserId($userId);
        
        if (!$preference) {
            $preference = new UserPreference();
            $preference->setUserId($userId);
        }
        
        return $preference;
    }
}

