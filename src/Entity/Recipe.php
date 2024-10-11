<?php

namespace App\Entity;

use App\Entity\Ingredient;
use App\Entity\RecipeStep;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\RecipeRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
#[Vich\Uploadable]
class Recipe
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['recipe'])]
    private ?int $id = null;

    // Titre: Ne peut être vide, limite de 255 caractères
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.", groups: ['create', 'update'])]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le titre ne doit pas dépasser {{ limit }} caractères.",
        groups: ['create', 'update']
    )]
    #[Groups(['recipe'])]
    private ?string $title = null;

    // Description: Ne peut être vide
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "La description est obligatoire.", groups: ['create', 'update'])]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit faire au moins {{ limit }} caractères.",
        groups: ['create', 'update']
    )]
    #[Groups(['recipe'])]
    private ?string $description = null;

    #[Vich\UploadableField(mapping: 'recipes', fileNameProperty: 'imageName')]
    #[Assert\File(
        maxSize: "2M",
        maxSizeMessage: "L'image ne doit pas dépasser 2 Mo.",
        mimeTypes: ["image/jpeg", "image/png", "image/webp"],
        mimeTypesMessage: "Veuillez télécharger une image valide (JPEG, PNG ou WEBP).",
        groups: ['create', 'update']
    )]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['recipe'])]
    private ?string $imageName = null;

    // Difficulté: Valeur entre 1 et 5 (par exemple)
    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "La difficulté est obligatoire.", groups: ['create', 'update'])]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "La difficulté doit être entre {{ min }} et {{ max }}.",
        groups: ['create', 'update']
    )]
    #[Groups(['recipe'])]
    private ?int $difficulty = null;

    // Temps de préparation: Valeur positive
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le temps de préparation doit être un nombre positif.", groups: ['create', 'update'])]
    #[Groups(['recipe'])]
    private ?int $preparation_time = null;

    // Temps de cuisson: Valeur positive
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le temps de cuisson doit être un nombre positif.", groups: ['create', 'update'])]
    #[Groups(['recipe'])]
    private ?int $cooking_time = null;

    // Temps de repos: Valeur positive ou nulle
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le temps de repos doit être un nombre positif.", groups: ['create', 'update'])]
    #[Groups(['recipe'])]
    private ?int $rest_time = null;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'recipes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['recipe'])]
    private ?Profile $profile = null;

    /**
     * @var Collection<int, RecipeStep>
     */
    #[ORM\OneToMany(targetEntity: RecipeStep::class,mappedBy: 'recipe', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Assert\Valid(groups: ['create', 'update'])]
    #[Groups(['recipe'])]
    private Collection $steps;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Ingredient>
     */
    #[ORM\OneToMany(targetEntity: Ingredient::class, mappedBy: 'recipe', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Assert\Valid(groups: ['create', 'update'])]
    #[Groups(['recipe'])]
    private Collection $ingredients;

    #[ORM\Column]
    #[Groups(['recipe'])]
    private ?bool $isActive = null;

    // Nombre de personnes: Nombre supérieur à 0
    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Le nombre de personnes est obligatoire.", groups: ['create', 'update'])]
    #[Assert\Positive(message: "Le nombre de personnes doit être supérieur à 0.", groups: ['create', 'update'])]
    #[Groups(['recipe'])]
    private ?int $nbGuest = null;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'recipe', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $ratings;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'recipe', orphanRemoval: true)]
    private Collection $comments;


    public function __construct()
    {
        $this->steps = new ArrayCollection();
        $this->ingredients = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    // Getters and setters...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getDifficulty(): ?int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getPreparationTime(): ?int
    {
        return $this->preparation_time;
    }

    public function setPreparationTime(int $preparation_time): self
    {
        $this->preparation_time = $preparation_time;

        return $this;
    }

    public function getCookingTime(): ?int
    {
        return $this->cooking_time;
    }

    public function setCookingTime(int $cooking_time): self
    {
        $this->cooking_time = $cooking_time;

        return $this;
    }

    public function getRestTime(): ?int
    {
        return $this->rest_time;
    }

    public function setRestTime(?int $rest_time): self
    {
        $this->rest_time = $rest_time;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return Collection<int, RecipeStep>
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(RecipeStep $step): self
    {
        if (!$this->steps->contains($step)) {
            $this->steps[] = $step;
            $step->setRecipe($this);
        }

        return $this;
    }

    public function removeStep(RecipeStep $step): self
    {
        if ($this->steps->removeElement($step)) {
            // set the owning side to null (unless already changed)
            if ($step->getRecipe() === $this) {
                $step->setRecipe(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ingredient>
     */
    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    public function addIngredient(Ingredient $ingredient): static
    {
        if (!$this->ingredients->contains($ingredient)) {
            $this->ingredients->add($ingredient);
            $ingredient->setRecipe($this);
        }

        return $this;
    }

    public function removeIngredient(Ingredient $ingredient): static
    {
        if ($this->ingredients->removeElement($ingredient)) {
            // set the owning side to null (unless already changed)
            if ($ingredient->getRecipe() === $this) {
                $ingredient->setRecipe(null);
            }
        }

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getNbGuest(): ?int
    {
        return $this->nbGuest;
    }

    public function setNbGuest(int $nbGuest): static
    {
        $this->nbGuest = $nbGuest;

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setRecipe($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getRecipe() === $this) {
                $rating->setRecipe(null);
            }
        }

        return $this;
    }
    public function getAverageRating(): ?float
    {
        $totalRatings = count($this->ratings);
        if ($totalRatings === 0) {
            return null; // Si aucune note n'est présente
        }

        $sum = array_reduce($this->ratings->toArray(), function ($carry, $rating) {
            return $carry + $rating->getScore();
        }, 0);

        return $sum / $totalRatings;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setRecipe($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getRecipe() === $this) {
                $comment->setRecipe(null);
            }
        }

        return $this;
    }

}