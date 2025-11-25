<?php

namespace App\Entity;

use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPreferenceRepository::class)]
#[ORM\Table(name: 'user_preferences')]
class UserPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $userId;

    #[ORM\Column(type: 'boolean')]
    private bool $sendReadReceipts = true;

    #[ORM\Column(type: 'boolean')]
    private bool $receiveReadReceipts = true;

    #[ORM\Column(type: 'boolean')]
    private bool $showStatistics = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getSendReadReceipts(): bool
    {
        return $this->sendReadReceipts;
    }

    public function setSendReadReceipts(bool $sendReadReceipts): self
    {
        $this->sendReadReceipts = $sendReadReceipts;
        return $this;
    }

    public function getReceiveReadReceipts(): bool
    {
        return $this->receiveReadReceipts;
    }

    public function setReceiveReadReceipts(bool $receiveReadReceipts): self
    {
        $this->receiveReadReceipts = $receiveReadReceipts;
        return $this;
    }

    public function getShowStatistics(): bool
    {
        return $this->showStatistics;
    }

    public function setShowStatistics(bool $showStatistics): self
    {
        $this->showStatistics = $showStatistics;
        return $this;
    }
}

