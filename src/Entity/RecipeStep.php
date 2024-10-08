<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\RecipeStepRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecipeStepRepository::class)]
class RecipeStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "La description de l'étape est obligatoire." , groups: ['create', 'update'])]
    #[Assert\Length(
        min: 10,
        minMessage: "La description de l'étape doit faire au moins {{ limit }} caractères.",
        groups: ['create', 'update']
    )]
    private ?string $stepDescription = null;

    #[ORM\Column(type: 'integer')]
    private ?int $stepNumber = null;

    #[ORM\ManyToOne(inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $recipe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStepDescription(): ?string
    {
        return $this->stepDescription;
    }

    public function setStepDescription(string $stepDescription): self
    {
        $this->stepDescription = $stepDescription;

        return $this;
    }

    public function getStepNumber(): ?int
    {
        return $this->stepNumber;
    }

    public function setStepNumber(int $stepNumber): self
    {
        $this->stepNumber = $stepNumber;

        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }
}