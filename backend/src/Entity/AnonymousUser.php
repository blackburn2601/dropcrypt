<?php

namespace App\Entity;

use App\Repository\AnonymousUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnonymousUserRepository::class)]
#[ORM\Table(name: 'anonymous_users')]
class AnonymousUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $anonymousId;

    #[ORM\Column(length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'text')]
    private string $publicKey;

    #[ORM\Column(length: 64, unique: true)]
    private string $recoveryPhraseHash;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    private int $messagesSentCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $messagesReceivedCount = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->anonymousId = 'usr_' . bin2hex(random_bytes(8));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnonymousId(): string
    {
        return $this->anonymousId;
    }

    public function setAnonymousId(string $anonymousId): self
    {
        $this->anonymousId = $anonymousId;
        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    public function getRecoveryPhraseHash(): string
    {
        return $this->recoveryPhraseHash;
    }

    public function setRecoveryPhraseHash(string $recoveryPhraseHash): self
    {
        $this->recoveryPhraseHash = $recoveryPhraseHash;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function setMessagesSentCount(int $count): self
    {
        $this->messagesSentCount = $count;
        return $this;
    }

    public function setMessagesReceivedCount(int $count): self
    {
        $this->messagesReceivedCount = $count;
        return $this;
    }

    public function getMessagesSentCount(): int
    {
        return $this->messagesSentCount;
    }

    public function incrementMessagesSentCount(): self
    {
        $this->messagesSentCount++;
        return $this;
    }

    public function getMessagesReceivedCount(): int
    {
        return $this->messagesReceivedCount;
    }

    public function incrementMessagesReceivedCount(): self
    {
        $this->messagesReceivedCount++;
        return $this;
    }
}

