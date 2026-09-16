<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\IssueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IssueRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['issue:list', 'issue:item']],
)]
class Issue
{
    public const VISIBILITIES = ['public', 'private'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent', 'immediate'];
    public const SEVERITIES = ['trivial', 'minor', 'major', 'critical', 'blocker'];
    public const STATUSES = ['new', 'accepted', 'confirmed', 'assigned', 'processed', 'closed'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['issue:list', 'issue:item'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'issues')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Project $project = null;

    #[ORM\ManyToOne(inversedBy: 'issues')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Category $category = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::VISIBILITIES)]
    private ?string $visibility = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $submittedAt = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'reportedIssues')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $reporter = null;

    #[ORM\ManyToOne(inversedBy: 'assignedIssues')]
    private ?User $assigned = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::PRIORITIES)]
    private ?string $priority = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::SEVERITIES)]
    private ?string $severity = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $stepsToReproduce = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::STATUSES)]
    private ?string $status = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $summary = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachment = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resolvedAt = null;

    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $environment = null;

    #[ORM\OneToMany(mappedBy: 'issue', targetEntity: Comment::class)]
    private Collection $comments;

    #[ORM\ManyToMany(targetEntity: self::class)]
    private Collection $relations;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->relations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeInterface $submittedAt): self
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getAssigned(): ?User
    {
        return $this->assigned;
    }

    public function setAssigned(?User $assigned): self
    {
        $this->assigned = $assigned;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;

        return $this;
    }

    public function getStepsToReproduce(): ?string
    {
        return $this->stepsToReproduce;
    }

    public function setStepsToReproduce(?string $stepsToReproduce): self
    {
        $this->stepsToReproduce = $stepsToReproduce;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeInterface $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public function setEnvironment(?string $environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setIssue($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getIssue() === $this) {
                $comment->setIssue(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function addRelation(self $relation): self
    {
        if (!$this->relations->contains($relation)) {
            $this->relations->add($relation);
        }

        return $this;
    }

    public function removeRelation(self $relation): self
    {
        $this->relations->removeElement($relation);

        return $this;
    }
}
