<?php declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata as API;
use ApiPlatform\Metadata\ApiFilter;
use App\State\RandomProductsProvider;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[API\ApiResource(
    paginationItemsPerPage: 20,
    operations: [
        // /api/products (kollekció)
        new API\GetCollection(),

        // /api/products/{id} (ITEM) – csak számjegy lehet az id!
        new API\Get(
            uriTemplate: '/products/{id}',
            requirements: ['id' => '\d+']
        ),

        // /api/products/random (kollekció, saját providerrel)
        new API\GetCollection(
            name: 'random_products',
            uriTemplate: '/products/random',
            provider: RandomProductsProvider::class,
            paginationEnabled: false
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'description' => 'partial',
    'category.name' => 'partial',
])]
#[ApiFilter(RangeFilter::class, properties: ['priceCents', 'netPriceCents'])]
#[ApiFilter(OrderFilter::class, properties: ['priceCents', 'name', 'createdAt'], arguments: ['orderParameterName' => 'order'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $identifier = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column]
    private int $priceCents = 0;

    #[ORM\Column]
    private int $netPriceCents = 0;

    #[ORM\Column(length: 1024)]
    private string $imageUrl = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    // getters/setters…
    public function getId(): ?int { return $this->id; }
    public function getIdentifier(): ?string { return $this->identifier; }
    public function setIdentifier(string $identifier): self { $this->identifier = $identifier; return $this; }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(Category $category): self { $this->category = $category; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getPriceCents(): int { return $this->priceCents; }
    public function setPriceCents(int $priceCents): self { $this->priceCents = $priceCents; return $this; }
    public function getNetPriceCents(): int { return $this->netPriceCents; }
    public function setNetPriceCents(int $netPriceCents): self { $this->netPriceCents = $netPriceCents; return $this; }
    public function getImageUrl(): string { return $this->imageUrl; }
    public function setImageUrl(string $imageUrl): self { $this->imageUrl = $imageUrl; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
