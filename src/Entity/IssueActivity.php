<?php

namespace App\Entity;

use App\Repository\IssueActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One recorded change to an issue's status, priority or assignee.
 *
 * The actor is kept as a nullable link (set to null automatically if the
 * account is later deleted) plus a snapshot username, so the row still
 * reads correctly on its own once the link is gone.
 */
#[ORM\Entity(repositoryClass: IssueActivityRepository::class)]
class IssueActivity
{
    public const FIELD_STATUS = 'status';
    public const FIELD_PRIORITY = 'priority';
    public const FIELD_ASSIGNED = 'assigned';
    public const FIELDS = [self::FIELD_STATUS, self::FIELD_PRIORITY, self::FIELD_ASSIGNED];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Issue $issue = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(length: 180)]
    private ?string $actorUsername = null;

    #[ORM\Column(length: 20)]
    private ?string $field = null;

    /** For status/priority, the enum value. For assigned, the username, or null for "unassigned". */
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $oldValue = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $newValue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function setActorUsername(string $actorUsername): self
    {
        $this->actorUsername = $actorUsername;

        return $this;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    public function setOldValue(?string $oldValue): self
    {
        $this->oldValue = $oldValue;

        return $this;
    }

    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    public function setNewValue(?string $newValue): self
    {
        $this->newValue = $newValue;

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
