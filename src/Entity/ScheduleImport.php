<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'schedule_import')]
class ScheduleImport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column]
    private \DateTimeImmutable $importedAt;

    #[ORM\Column(length: 64)]
    private string $sourceHash = '';

    #[ORM\Column]
    private int $eventCount = 0;

    #[ORM\Column]
    private int $venueCount = 0;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $sourceUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function setImportedAt(\DateTimeImmutable $importedAt): static
    {
        $this->importedAt = $importedAt;

        return $this;
    }

    public function getSourceHash(): string
    {
        return $this->sourceHash;
    }

    public function setSourceHash(string $sourceHash): static
    {
        $this->sourceHash = $sourceHash;

        return $this;
    }

    public function getEventCount(): int
    {
        return $this->eventCount;
    }

    public function setEventCount(int $eventCount): static
    {
        $this->eventCount = $eventCount;

        return $this;
    }

    public function getVenueCount(): int
    {
        return $this->venueCount;
    }

    public function setVenueCount(int $venueCount): static
    {
        $this->venueCount = $venueCount;

        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): static
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }
}
