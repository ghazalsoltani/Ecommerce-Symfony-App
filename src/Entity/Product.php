<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource( // Expose this entity as an API endpoint
    operations: [
        new GetCollection(),
        new Get()
    ],
    order: ['id' => 'DESC'],
    paginationEnabled: false,
    normalizationContext: ['groups' => ['product:read']]
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['product:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $illustration = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?float $tva = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(['product:read'])]
    private ?Category $category = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?bool $isHomepage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getIllustration(): ?string
    {
        return $this->illustration;
    }

    public function setIllustration(string $illustration): static
    {
        $this->illustration = $illustration;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getPriceWt()
    {
        $coeff = 1 + ($this->tva/100);
        return $coeff * $this->price;
    }

    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(float $tva): static
    {
        $this->tva = $tva;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isHomepage(): ?bool
    {
        return $this->isHomepage;
    }

    public function getIsHomepage(): ?bool
    {
        return $this->isHomepage;
    }

    public function setIsHomepage(?bool $isHomepage): static
    {
        $this->isHomepage = $isHomepage;
        return $this;
    }
}
