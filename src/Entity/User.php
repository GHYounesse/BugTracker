<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 180)]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(mappedBy: 'reporter', targetEntity: Issue::class)]
    private Collection $reportedIssues;

    #[ORM\OneToMany(mappedBy: 'assigned', targetEntity: Issue::class)]
    private Collection $assignedIssues;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Comment::class)]
    private Collection $comments;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __construct()
    {
        $this->reportedIssues = new ArrayCollection();
        $this->assignedIssues = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Issue>
     */
    public function getReportedIssues(): Collection
    {
        return $this->reportedIssues;
    }

    public function addReportedIssue(Issue $issue): self
    {
        if (!$this->reportedIssues->contains($issue)) {
            $this->reportedIssues->add($issue);
            $issue->setReporter($this);
        }

        return $this;
    }

    public function removeReportedIssue(Issue $issue): self
    {
        if ($this->reportedIssues->removeElement($issue)) {
            // set the owning side to null (unless already changed)
            if ($issue->getReporter() === $this) {
                $issue->setReporter(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Issue>
     */
    public function getAssignedIssues(): Collection
    {
        return $this->assignedIssues;
    }

    public function addAssignedIssue(Issue $issue): self
    {
        if (!$this->assignedIssues->contains($issue)) {
            $this->assignedIssues->add($issue);
            $issue->setAssigned($this);
        }

        return $this;
    }

    public function removeAssignedIssue(Issue $issue): self
    {
        if ($this->assignedIssues->removeElement($issue)) {
            // set the owning side to null (unless already changed)
            if ($issue->getAssigned() === $this) {
                $issue->setAssigned(null);
            }
        }

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
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }
}
