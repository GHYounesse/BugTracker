<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One thing a user should know about: they were assigned an issue, mentioned
 * in a comment, someone commented on an issue they're involved with, or an
 * issue they're on is due soon.
 *
 * Deleting the recipient deletes their notifications with them (there is
 * nothing left to show once the account is gone). The actor who caused it
 * (not set for due_soon, which is system-triggered) follows the same
 * nullable-link-plus-snapshot pattern as IssueActivity/Attachment, so a row
 * still reads correctly once that account is gone.
 */
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    public const TYPE_ASSIGNED = 'assigned';
    public const TYPE_MENTIONED = 'mentioned';
    public const TYPE_COMMENTED = 'commented';
    public const TYPE_DUE_SOON = 'due_soon';
    public const TYPES = [self::TYPE_ASSIGNED, self::TYPE_MENTIONED, self::TYPE_COMMENTED, self::TYPE_DUE_SOON];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $recipient = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Issue $issue = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Comment $comment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    /** Null for due_soon, which has no actor. */
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $actorUsername = null;

    /** Short preview of the comment content, for mentioned/commented notifications. */
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column]
    private bool $read = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getIssue(): ?Issue
    {
        return $this->issue;
    }

    public function setIssue(?Issue $issue): self
    {
        $this->issue = $issue;

        return $this;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): self
    {
        $this->actor = $actor;

        return $this;
    }

    public function getActorUsername(): ?string
    {
        return $this->actorUsername;
    }

    public function setActorUsername(?string $actorUsername): self
    {
        $this->actorUsername = $actorUsername;

        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function setRead(bool $read): self
    {
        $this->read = $read;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
