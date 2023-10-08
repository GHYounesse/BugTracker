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
use Symfony\Component\Serializer\Annotation\Groups;




#[ORM\Entity(repositoryClass: IssueRepository::class)]
#[ApiResource()]
class Issue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['issue:list', 'issue:item'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'issues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(inversedBy: 'issues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    private ?string $visibilite = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_soumission = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_mise_jour = null;

    #[ORM\ManyToOne(inversedBy: 'issues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $rapporteur = null;

    #[ORM\ManyToOne(inversedBy: 'assignements')]
    private ?User $assigned = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    private ?string $priorite = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    private ?string $severite = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reproduce = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    private ?string $etat = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    private ?string $resume = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    private ?string $description = null;
    #[Groups(['issue:list', 'issue:item'])]
    #[ORM\Column(length: 255)]
    private ?string $tags = null;

    #[ORM\OneToMany(mappedBy: 'issue', targetEntity: Activite::class)]
    private Collection $activites;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'relations')]
    private Collection $relations;

    public function __construct()
    {
        $this->activites = new ArrayCollection();
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

    public function getVisibilite(): ?string
    {
        return $this->visibilite;
    }

    public function setVisibilite(string $visibilite): self
    {
        $this->visibilite = $visibilite;

        return $this;
    }

    public function getDateSoumission(): ?string
    {
        return $this->date_soumission->format('Y-m-d H:i:s');
    }

    public function setDateSoumission(\DateTimeInterface $date_soumission): self
    {
        $this->date_soumission = $date_soumission;

        return $this;
    }

    public function getDateMiseJour(): ?string
    {
        return $this->date_mise_jour->format('Y-m-d H:i:s');;
    }

    public function setDateMiseJour(\DateTimeInterface $date_mise_jour): self
    {
        $this->date_mise_jour = $date_mise_jour;

        return $this;
    }

    public function getRapporteur(): ?User
    {
        return $this->rapporteur;
    }

    public function setRapporteur(?User $rapporteur): self
    {
        $this->rapporteur = $rapporteur;

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

    public function getPriorite(): ?string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): self
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getSeverite(): ?string
    {
        return $this->severite;
    }

    public function setSeverite(string $severite): self
    {
        $this->severite = $severite;

        return $this;
    }

    public function getReproduce(): ?string
    {
        return $this->reproduce;
    }

    public function setReproduce(?string $reproduce): self
    {
        $this->reproduce = $reproduce;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(string $resume): self
    {
        $this->resume = $resume;

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

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(string $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return Collection<int, Activite>
     */
    public function getActivites(): Collection
    {
        return $this->activites;
    }

    public function addActivite(Activite $activite): self
    {
        if (!$this->activites->contains($activite)) {
            $this->activites->add($activite);
            $activite->setIssue($this);
        }

        return $this;
    }

    public function removeActivite(Activite $activite): self
    {
        if ($this->activites->removeElement($activite)) {
            // set the owning side to null (unless already changed)
            if ($activite->getIssue() === $this) {
                $activite->setIssue(null);
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
