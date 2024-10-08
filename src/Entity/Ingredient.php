<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\IngredientRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'ingrédient est obligatoire.', groups: ['create', 'update'])]

    private ?string $name = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'La quantité est obligatoire.', groups: ['create', 'update'])]
    #[Assert\GreaterThan(0, message: 'La quantité doit être supérieure à 0.', groups: ['create', 'update'])]
    
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'ingredients')]
    #[ORM\JoinColumn(nullable: false)]
    
    private ?Recipe $recipe = null;

    #[ORM\ManyToOne]
    private ?Unit $unit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): static
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): static
    {
        $this->unit = $unit;

        return $this;
    }
}