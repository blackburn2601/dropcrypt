<?php

namespace App\Entity;

use App\Repository\DirectMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DirectMessageRepository::class)]
#[ORM\Table(name: 'direct_messages')]
class DirectMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $messageId;

    #[ORM\Column(length: 64)]
    private string $senderId;

    #[ORM\Column(length: 64)]
    private string $recipientId;

    #[ORM\Column(type: 'text')]
    private string $encryptedContent;

    #[ORM\Column(type: 'text')]
    private string $encryptedKeyForRecipient;

    #[ORM\Column(type: 'text')]
    private string $encryptedKeyForSender;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $expiresAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $readAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;

    public function __construct()
    {
        $this->messageId = 'msg_' . bin2hex(random_bytes(12));
        $this->createdAt = new \DateTime();
        // Default: expires 24 hours after creation
        $this->expiresAt = (new \DateTime())->modify('+24 hours');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    public function getSenderId(): string
    {
        return $this->senderId;
    }

    public function setSenderId(string $senderId): self
    {
        $this->senderId = $senderId;
        return $this;
    }

    public function getRecipientId(): string
    {
        return $this->recipientId;
    }

    public function setRecipientId(string $recipientId): self
    {
        $this->recipientId = $recipientId;
        return $this;
    }

    public function getEncryptedContent(): string
    {
        return $this->encryptedContent;
    }

    public function setEncryptedContent(string $encryptedContent): self
    {
        $this->encryptedContent = $encryptedContent;
        return $this;
    }

    public function getEncryptedKeyForRecipient(): string
    {
        return $this->encryptedKeyForRecipient;
    }

    public function setEncryptedKeyForRecipient(string $encryptedKeyForRecipient): self
    {
        $this->encryptedKeyForRecipient = $encryptedKeyForRecipient;
        return $this;
    }

    public function getEncryptedKeyForSender(): string
    {
        return $this->encryptedKeyForSender;
    }

    public function setEncryptedKeyForSender(string $encryptedKeyForSender): self
    {
        $this->encryptedKeyForSender = $encryptedKeyForSender;
        return $this;
    }

    public function getSentAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getIsRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        if ($isRead && $this->readAt === null) {
            $this->readAt = new \DateTime();
        }
        return $this;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): self
    {
        $this->readAt = $readAt ? \DateTime::createFromImmutable($readAt) : null;
        return $this;
    }

    public function getReadAt(): ?\DateTime
    {
        return $this->readAt;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }
}

